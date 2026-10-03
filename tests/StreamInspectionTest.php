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

use Contao\Image\Dimensions;
use Contao\Image\Exception\ImageDecodingException;
use Contao\Image\Exception\ImageStorageException;
use Contao\Image\Exception\SourceVersionMismatchException;
use Contao\Image\Exception\UnsupportedImageFormatException;
use Contao\Image\FilesystemSourceInformationStorage;
use Contao\Image\SourceInformationProvider;
use Contao\Image\SourceVersion;
use Contao\Image\StreamImageInspector;
use Contao\Image\Tests\Fixture\MemorySource;
use Symfony\Component\Filesystem\Filesystem;

class StreamInspectionTest extends AbstractImagePipelineTest
{
    public function testNonSeekableEarlyClosureAndWarmCache(): void
    {
        $this->source();
        $source = new MemorySource(file_get_contents($this->directory.'/source').str_repeat('trailing-data', 10000));
        $provider = new SourceInformationProvider(new FilesystemSourceInformationStorage($this->directory.'/information'), new StreamImageInspector());
        $version = SourceVersion::capture($source);
        $information = $provider->get($source, $version);
        $this->assertSame('jpg', $information->getFormat());
        $this->assertSame([120, 80], $information->getDimensions()->getSize()->toArray());
        $this->assertSame(1, $source->opens);
        $this->assertSame(1, $source->closes);
        $this->assertLessThan(\strlen($source->contents), $source->bytesRead);
        $reads = $source->bytesRead;
        $provider->get($source, $version);
        $this->assertSame(1, $source->opens);
        $this->assertSame($reads, $source->bytesRead);
    }

    public function testRasterFormatDetection(): void
    {
        foreach (['png', 'gif', 'webp'] as $format) {
            $this->source($format);
            $source = new MemorySource(file_get_contents($this->directory.'/source'));
            $information = (new StreamImageInspector())->inspect($source);
            $this->assertSame($format, $information->getFormat());
            $this->assertSame([120, 80], $information->getDimensions()->getSize()->toArray());
            $this->assertSame($source->opens, $source->closes);
        }
    }

    public function testSvgSizingAndCompressedInput(): void
    {
        foreach ([['width="120" height="80"', Dimensions::ABSOLUTE], ['viewBox="0 0 120 80"', Dimensions::RELATIVE], ['', Dimensions::UNDEFINED]] as [$attributes, $kind]) {
            foreach ([false, true] as $gzip) {
                $xml = '<svg xmlns="http://www.w3.org/2000/svg" '.$attributes.'>'.str_repeat(' ', 20000).'</svg>';
                $source = new MemorySource($gzip ? gzencode($xml) : $xml);
                $information = (new StreamImageInspector())->inspect($source);
                $this->assertSame($gzip ? 'svgz' : 'svg', $information->getFormat());
                $this->assertSame($kind, $information->getDimensions()->toArray()['kind']);
                $this->assertSame(1, $source->closes);
            }
        }
    }

    public function testOrientationAndMalformedCleanup(): void
    {
        $this->source();
        $jpeg = file_get_contents($this->directory.'/source');
        $exif = "Exif\0\0II\x2a\0\x08\0\0\0\x01\0\x12\x01\x03\0\x01\0\0\0\x06\0\0\0\0\0\0\0";
        $source = new MemorySource(substr($jpeg, 0, 2)."\xff\xe1".pack('n', \strlen($exif) + 2).$exif.substr($jpeg, 2));
        $information = (new StreamImageInspector())->inspect($source);
        $this->assertSame([80, 120], $information->getDimensions()->getSize()->toArray());
        $this->assertSame(6, $information->getDimensions()->getOrientation());
        $invalid = new MemorySource('not an image');

        try {
            (new StreamImageInspector())->inspect($invalid);
            $this->fail('Expected decoding failure');
        } catch (ImageDecodingException|UnsupportedImageFormatException) {
            $this->assertSame(1, $invalid->closes);
        }
    }

    public function testSchemaAndCorruptionRecovery(): void
    {
        $source = $this->source();
        $version = SourceVersion::capture($source);
        $storage = new FilesystemSourceInformationStorage($this->directory.'/info');
        $provider = new SourceInformationProvider($storage, new StreamImageInspector());
        $provider->get($source, $version);
        $path = $this->directory.'/info/information/'.$version->getPath().'.json';
        (new Filesystem())->dumpFile($path, '{bad json');
        $this->assertSame('jpg', $provider->get($source, $version)->getFormat());
        $data = json_decode(file_get_contents($path), true);
        $data['schema'] = 900;
        (new Filesystem())->dumpFile($path, json_encode($data));
        $this->assertSame('jpg', $provider->get($source, $version)->getFormat());
    }

    public function testChangingStreamIsRejectedAndClosed(): void
    {
        $this->source();
        $source = new MemorySource(file_get_contents($this->directory.'/source'));
        $source->onRead = static function () use ($source): void { $source->contents .= 'changed'; $source->onRead = null; };

        try {
            (new StreamImageInspector())->inspect($source);
            $this->fail('Expected changed stream rejection');
        } catch (SourceVersionMismatchException) {
            $this->assertSame(1, $source->closes);
        }
    }

    public function testReadFailureClosesOwnedStream(): void
    {
        $source = new MemorySource('broken');
        $source->onRead = static function (): void { throw new ImageStorageException('Injected read failure'); };

        try {
            (new StreamImageInspector())->inspect($source);
            $this->fail('Expected read failure');
        } catch (ImageStorageException) {
            $this->assertSame(1, $source->closes);
        }
    }
}
