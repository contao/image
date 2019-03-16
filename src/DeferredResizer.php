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

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class DeferredResizer extends Resizer implements DeferredResizerInterface
{
    /**
     * @var DeferredImageStorageInterface
     *
     * @internal
     */
    private $storage;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $cacheDir, ResizeCalculatorInterface $calculator = null, Filesystem $filesystem = null, DeferredImageStorageInterface $storage = null)
    {
        parent::__construct($cacheDir, $calculator, $filesystem);

        if (null === $storage) {
            $this->storage = new DeferredImageStorageFilesystem($cacheDir);
        }
    }

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
            $this->cacheDir.'/'.$targetPath,
            $imagine,
            new ImageDimensions(
                new Box(
                    $config['coordinates']['crop']['width'],
                    $config['coordinates']['crop']['height']
                )
            )
        );
    }

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ?ImageInterface
    {
        if (!Path::isBasePath($this->cacheDir, $image->getPath())) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not inside cache directory "%s"', $image->getPath(), $this->cacheDir));
        }

        $targetPath = Path::makeRelative($image->getPath(), $this->cacheDir);
        $config = $this->storage->getLocked($targetPath, $blocking);

        if (null === $config) {
            return null;
        }

        try {
            $resizedImage = $this->executeDeferredResize($targetPath, $config, $image->getImagine());
        } finally {
            $this->storage->releaseLock($targetPath);
        }

        $this->storage->delete($targetPath);

        return $resizedImage;
    }

    protected function processResize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options): ImageInterface
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
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, string $path, ResizeOptionsInterface $options): ImageInterface
    {
        if (null !== $options->getTargetPath() || $options->getBypassCache()) {
            return parent::executeResize($image, $coordinates, $path, $options);
        }

        $this->storeResizeData($image->getPath(), $path, $coordinates, $options);

        return new DeferredImage($path, $image->getImagine(), new ImageDimensions($coordinates->getCropSize()));
    }

    private function storeResizeData(string $sourcePath, string $targetPath, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options): void
    {
        $targetPath = Path::makeRelative($targetPath, $this->cacheDir);

        if ($this->storage->has($targetPath)) {
            return;
        }

        $this->storage->set($targetPath, [
            'path' => $sourcePath,
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

        return parent::executeResize(
            new Image($config['path'], $imagine, $this->filesystem),
            $coordinates,
            $this->cacheDir.'/'.$targetPath,
            $options
        );
    }
}
