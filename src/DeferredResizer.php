<?php

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
     *
     * @param $storage DeferredImageStorageInterface
     */
    public function __construct($cacheDir, ResizeCalculatorInterface $calculator = null, Filesystem $filesystem = null, DeferredImageStorageInterface $storage = null)
    {
        parent::__construct($cacheDir, $calculator, $filesystem);

        if (null === $storage) {
            $this->storage = new DeferredImageStorageFilesystem($cacheDir);
        }
    }

    public function getDeferredImage($targetPath)
    {
        if (!$this->storage->has($targetPath)) {
            return null;
        }

        $config = $this->storage->get($targetPath);

        return new DeferredImage(
            $this->cacheDir.'/'.$targetPath,
            new ImageDimensions(
                new Box(
                    $config['coordinates']['crop']['width'],
                    $config['coordinates']['crop']['height']
                )
            )
        );
    }

    public function resizeDeferredImage($targetPath, ImagineInterface $imagine)
    {
        $config = $this->storage->getLocked($targetPath);

        try {
            $image = $this->executeDeferredResize($targetPath, $config, $imagine);
        } finally {
            $this->storage->releaseLock($targetPath);
        }

        $this->storage->delete($targetPath);

        return $image;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, $path, ResizeOptionsInterface $options)
    {
        if (null !== $options->getTargetPath() || $options->getBypassCache()) {
            return parent::executeResize($image, $coordinates, $path, $options);
        }

        $this->storeResizeData($image->getPath(), $path, $coordinates, $options);

        return new DeferredImage($path, new ImageDimensions($coordinates->getCropSize()));
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     */
    private function storeResizeData($sourcePath, $targetPath, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options)
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

    private function executeDeferredResize($targetPath, $config, ImagineInterface $imagine)
    {
        $sourcePath = $config['path'];
        $coordinates = new ResizeCoordinates(
            new Box($config['coordinates']['size']['width'], $config['coordinates']['size']['height']),
            new Point($config['coordinates']['crop']['x'], $config['coordinates']['crop']['y']),
            new Box($config['coordinates']['crop']['width'], $config['coordinates']['crop']['height'])
        );
        $options = new ResizeOptions();
        $options->setImagineOptions($config['options']['imagine_options']);

        return parent::executeResize(
            new Image($sourcePath, $imagine, $this->filesystem),
            $coordinates,
            $this->cacheDir.'/'.$targetPath,
            $options
        );
    }
}
