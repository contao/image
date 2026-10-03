<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\FilesystemImageConfiguration;
use Contao\Image\FilesystemImageFactory;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalImageResultInterface;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Imagine\Gd\Imagine;
use Symfony\Component\Filesystem\Filesystem;

class OrientationContractTest extends AbstractImagePipelineTest
{
    public function testInspectionControlsRotationWhenDriverOmitsMetadata(): void
    {
        $this->createOrientedSource();
        $driver = new class() extends Imagine {
            public function open($path)
            {
                $image = parent::open($path);
                unset($image->metadata()['ifd0.Orientation']);

                return $image;
            }
        };
        $factory = new FilesystemImageFactory(new FilesystemImageConfiguration($this->directory.'/oriented', '/images', 'secret'), $this->resolver, new ImagineImageDecoderEncoder($driver));
        $source = $this->resolver->resolve(new SourceReference('assets', 'source'));
        $result = $factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(40), new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $image = imagecreatefromjpeg($result->getPath());

        try {
            $right = imagecolorsforindex($image, imagecolorat($image, 30, 30));
            $left = imagecolorsforindex($image, imagecolorat($image, 10, 30));
            $this->assertGreaterThan(200, $right['red']);
            $this->assertGreaterThan(200, $left['blue']);
        } finally {
            unset($image);
        }
    }

    private function createOrientedSource(): void
    {
        $image = imagecreatetruecolor(120, 80);
        imagefilledrectangle($image, 0, 0, 119, 79, imagecolorallocate($image, 0, 0, 255));
        imagefilledrectangle($image, 0, 0, 119, 39, imagecolorallocate($image, 255, 0, 0));
        imagejpeg($image, $this->directory.'/source', 100);
        unset($image);
        $jpeg = file_get_contents($this->directory.'/source');
        $exif = "Exif\0\0II\x2a\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        (new Filesystem())->dumpFile($this->directory.'/source', substr($jpeg, 0, 2)."\xff\xe1".pack('n', \strlen($exif) + 2).$exif.substr($jpeg, 2));
    }
}
