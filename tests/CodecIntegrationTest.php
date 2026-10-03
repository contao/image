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
use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\MetadataReaderWriter;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Contao\ImagineSvg\Imagine;
use Symfony\Component\Filesystem\Filesystem;

class CodecIntegrationTest extends AbstractImagePipelineTest
{
    public function testSvgAndSvgzTransformation(): void
    {
        foreach (['svg', 'svgz'] as $format) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 80"><rect width="120" height="80" fill="red"/></svg>';
            (new Filesystem())->dumpFile($this->directory.'/source', 'svgz' === $format ? gzencode($svg) : $svg);
            $source = $this->resolver->resolve(new SourceReference('assets', 'source'));
            $result = $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), new ProcessingOptions());
            $this->assertInstanceOf(LocalImageResultInterface::class, $result);
            $this->assertSame($format, $result->getFormat());
            $output = (new Imagine())->open($result->getPath());
            $this->assertSame(60, $output->getSize()->getWidth());
            $this->assertSame(40, $output->getSize()->getHeight());
        }
    }

    public function testImagickCodec(): void
    {
        if (!\extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick is tested in the dedicated CI job');
        }
        $source = $this->source();
        $factory = new FilesystemImageFactory(new FilesystemImageConfiguration($this->directory.'/imagick', '/images', 'secret'), $this->resolver, new ImagineImageDecoderEncoder(new \Imagine\Imagick\Imagine()));
        $result = $factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertSame([60, 40], \array_slice(getimagesize($result->getPath()), 0, 2));
    }

    public function testMetadataAndOrientationUseSnapshot(): void
    {
        $this->source();
        $jpeg = file_get_contents($this->directory.'/source');
        $exif = "Exif\0\0II\x2a\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        (new Filesystem())->dumpFile($this->directory.'/source', substr($jpeg, 0, 2)."\xff\xe1".pack('n', \strlen($exif) + 2).$exif.substr($jpeg, 2));
        $source = $this->resolver->resolve(new SourceReference('assets', 'source'));
        $result = $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(40), new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertSame([40, 60], \array_slice(getimagesize($result->getPath()), 0, 2));
    }

    public function testCopyrightPreservationAndOptOut(): void
    {
        $this->source();
        $metadata = new MetadataReaderWriter();
        $options = new ProcessingOptions();
        $metadata->applyCopyrightToFile($this->directory.'/source', $this->directory.'/copyright', new ImageMetadata(['iptc' => ['2#116' => ['Copyright example']]]), $options->getPreserveCopyrightMetadata());
        $source = $this->resolver->resolve(new SourceReference('assets', 'copyright'));
        $result = $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), $options);
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertSame(['Copyright example'], $metadata->parse($result->getPath())->getFormat('iptc')['2#116']);
        $stripped = $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), $options->withPreserveCopyrightMetadata([]));
        $this->assertInstanceOf(LocalImageResultInterface::class, $stripped);
        $this->assertSame([], $metadata->parse($stripped->getPath())->getAll());
    }
}
