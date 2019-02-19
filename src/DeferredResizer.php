<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class DeferredResizer extends Resizer implements DeferredResizerInterface
{
    public function getDeferredImage($targetPath)
    {
        $imagePath = $this->cacheDir . '/' . $targetPath;
        $configPath = $imagePath.'.config';

        if (!$this->filesystem->exists($configPath)) {
            return null;
        }

        $config = json_decode(file_get_contents($configPath), true);

        return new DeferredImage(
            $imagePath,
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
        $configPath = $this->cacheDir . '/' . $targetPath.'.config';

        if (!$handle = fopen($configPath, 'r+') ?: fopen($configPath, 'r')) {
            throw new \RuntimeException(sprintf('Unable to open file "%s".', $configPath));
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new \RuntimeException(sprintf('Unable to acquire lock for file "%s".', $configPath));
        }

        try {
            $image = $this->executeDeferredResize($targetPath, json_decode(stream_get_contents($handle), true), $imagine);
        } finally {
            flock($handle, LOCK_UN | LOCK_NB);
            fclose($handle);
        }

        $this->filesystem->remove($configPath);

        return $image;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, $path, ResizeOptionsInterface $options)
    {
        if (null !== $options->getTargetPath()) {
            return parent::executeResize($image, $coordinates, $path, $options);
        }

        $this->storeResizeData($image->getPath(), $path, $coordinates, $options);

        return new DeferredImage($path, new ImageDimensions($coordinates->getCropSize()));
    }

    /**
     *
     */
    private function storeResizeData($sourcePath, $targetPath, ResizeCoordinatesInterface $coordinates, ResizeOptionsInterface $options)
    {
        $configPath = $targetPath.'.config';
        if ($this->filesystem->exists($configPath)) {
            return;
        }

        $this->filesystem->dumpFile($configPath, json_encode([
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
        ]));
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
            $this->cacheDir . '/' . $targetPath,
            $options
        );
    }
}
