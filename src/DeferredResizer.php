<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\RuntimeException;
use Contao\Image\Metadata\MetadataReaderWriter;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @method __construct(string $cacheDir, string $secret, ResizeCalculator $calculator = null, Filesystem $filesystem = null, DeferredImageStorageInterface $storage = null, MetadataReaderWriter $metadataReaderWriter = null)
 */
class DeferredResizer extends Resizer implements DeferredResizerInterface
{
    /**
     * @var DeferredImageStorageInterface
     *
     * @internal
     */
    private $storage;

    /**
     * @param string                             $cacheDir
     * @param string                             $secret
     * @param ResizeCalculator|null              $calculator
     * @param Filesystem|null                    $filesystem
     * @param DeferredImageStorageInterface|null $storage
     * @param MetadataReaderWriter|null          $metadataReaderWriter
     */
    public function __construct(string $cacheDir/*, string $secret, ResizeCalculator $calculator = null, Filesystem $filesystem = null, DeferredImageStorageInterface $storage = null, MetadataReaderWriter $metadataReaderWriter = null*/)
    {
        if (\func_num_args() > 1 && \is_string(func_get_arg(1))) {
            $secret = func_get_arg(1);
            $calculator = \func_num_args() > 2 ? func_get_arg(2) : null;
            $filesystem = \func_num_args() > 3 ? func_get_arg(3) : null;
            $storage = \func_num_args() > 4 ? func_get_arg(4) : null;
            $metadataReaderWriter = \func_num_args() > 5 ? func_get_arg(5) : null;
        } else {
            trigger_deprecation('contao/image', '1.2', 'Not passing a secret to "%s()" has been deprecated and will no longer work in version 2.0.', __METHOD__);
            $secret = null;
            $calculator = \func_num_args() > 1 ? func_get_arg(1) : null;
            $filesystem = \func_num_args() > 2 ? func_get_arg(2) : null;
            $storage = \func_num_args() > 3 ? func_get_arg(3) : null;
            $metadataReaderWriter = \func_num_args() > 4 ? func_get_arg(4) : null;
        }

        if (null === $storage) {
            $storage = new DeferredImageStorageFilesystem($cacheDir);
        }

        if (!$storage instanceof DeferredImageStorageInterface) {
            throw new \TypeError(sprintf('%s(): Argument #5 ($storage) must be of type DeferredImageStorageInterface|null, %s given', __METHOD__, get_debug_type($storage)));
        }

        if (null === $secret) {
            parent::__construct($cacheDir, $calculator, $filesystem, $metadataReaderWriter);
        } else {
            parent::__construct($cacheDir, $secret, $calculator, $filesystem, $metadataReaderWriter);
        }

        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeferredImage(string $targetPath, ImagineInterface $imagine): ?DeferredImageInterface
    {
        if (Path::isAbsolute($targetPath)) {
            if (!Path::isBasePath($this->cacheDir, $targetPath)) {
                return null;
            }

            $targetPath = Path::makeRelative($targetPath, $this->cacheDir);
        }

        if (!$this->storage->has($targetPath)) {
            return null;
        }

        $config = $this->storage->get($targetPath);

        return new DeferredImage(
            Path::join($this->cacheDir, $targetPath),
            $imagine,
            new ImageDimensions(
                new Box(
                    $config['coordinates']['crop']['width'],
                    $config['coordinates']['crop']['height']
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ?ImageInterface
    {
        if (!Path::isBasePath($this->cacheDir, $image->getPath())) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not inside cache directory "%s"', $image->getPath(), $this->cacheDir));
        }

        $targetPath = Path::makeRelative($image->getPath(), $this->cacheDir);

        try {
            $config = $this->storage->getLocked($targetPath, $blocking);
        } catch (\Throwable $exception) {
            // Getting the lock might fail if the image was already generated
            if ($this->filesystem->exists($image->getPath())) {
                return $blocking ? new Image($image->getPath(), $image->getImagine(), $this->filesystem) : null;
            }

            throw $exception;
        }

        if (null === $config) {
            if ($blocking) {
                throw new RuntimeException(sprintf('Unable to acquire lock for "%s"', $targetPath));
            }

            return null;
        }

        try {
            $resizedImage = $this->executeDeferredResize($targetPath, $config, $image->getImagine());
            $this->storage->delete($targetPath);
        } catch (\Throwable $exception) {
            $this->storage->releaseLock($targetPath);

            throw $exception;
        }

        return $resizedImage;
    }

    /**
     * {@inheritdoc}
     */
    protected function processResize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        // Resize the source image if it is deferred
        if ($image instanceof DeferredImageInterface) {
            $image = $this->resizeDeferredImage($image);
        }

        return parent::processResize($image, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinates $coordinates, string $path, ResizeOptions $options): ImageInterface
    {
        if (null !== $options->getTargetPath() || $options->getBypassCache()) {
            return parent::executeResize($image, $coordinates, $path, $options);
        }

        $this->storeResizeData($image->getPath(), $path, $coordinates, $options);

        return new DeferredImage($path, $image->getImagine(), new ImageDimensions($coordinates->getCropSize()));
    }

    private function storeResizeData(string $sourcePath, string $targetPath, ResizeCoordinates $coordinates, ResizeOptions $options): void
    {
        $targetPath = Path::makeRelative($targetPath, $this->cacheDir);

        if ($this->storage->has($targetPath)) {
            return;
        }

        $this->storage->set($targetPath, [
            'path' => Path::makeRelative($sourcePath, $this->cacheDir),
            'coordinates' => [
                'size' => [
                    'width' => $coordinates->getSize()->getWidth(),
                    'height' => $coordinates->getSize()->getHeight(),
                ],
                'crop' => [
                    'x' => $coordinates->getCropStart()->getX(),
                    'y' => $coordinates->getCropStart()->getY(),
                    'width' => $coordinates->getCropSize()->getWidth(),
                    'height' => $coordinates->getCropSize()->getHeight(),
                ],
            ],
            'options' => [
                'imagine_options' => $options->getImagineOptions(),
                'preserve_copyright' => $options->getPreserveCopyrightMetadata(),
            ],
        ]);
    }

    private function executeDeferredResize(string $targetPath, array $config, ImagineInterface $imagine): ImageInterface
    {
        $coordinates = new ResizeCoordinates(
            new Box($config['coordinates']['size']['width'], $config['coordinates']['size']['height']),
            new Point($config['coordinates']['crop']['x'], $config['coordinates']['crop']['y']),
            new Box($config['coordinates']['crop']['width'], $config['coordinates']['crop']['height'])
        );

        $options = new ResizeOptions();
        $options->setImagineOptions($config['options']['imagine_options']);

        if (isset($config['options']['preserve_copyright'])) {
            $options->setPreserveCopyrightMetadata($config['options']['preserve_copyright']);
        }

        $path = Path::join($this->cacheDir, $config['path']);

        return parent::executeResize(
            new Image($path, $imagine, $this->filesystem),
            $coordinates,
            Path::join($this->cacheDir, $targetPath),
            $options
        );
    }
}
