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

use Contao\Image\EncodingOptions;
use Contao\Image\Exception\ImageEncodingException;
use Contao\Image\ImageTransformation;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\StreamImageInspector;
use Contao\Image\TransformationCalculator;
use Imagine\Gd\Imagine;

class EncodingOptionsTest extends AbstractImagePipelineTest
{
    public function testCommonPrecedenceAndCanonicalIdentity(): void
    {
        $source = $this->source();
        $common = (new EncodingOptions())->withFormat('png')->withQuality(30)->withPngCompression(7)->withInterlacing('none')->withResampling('nearest');
        $bag = $common->withAdapterOptions('imagine', ['format' => 'gif', 'quality' => 90, 'png_compression_level' => 2, 'interlace' => 'plane', 'resampling-filter' => 'lanczos']);
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $first = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withEncoding($common));
        $second = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withEncoding($bag));
        $this->assertSame($first->getIdentity()->getPath(), $second->getIdentity()->getPath());
        $info = (new StreamImageInspector())->inspect($source);
        $transformation = new ImageTransformation($info, (new TransformationCalculator())->calculate($configuration, $info->getDimensions()), (new ProcessingOptions())->withEncoding($bag));
        $mapped = (new ImagineImageDecoderEncoder(new Imagine()))->mapOptions($transformation);
        $this->assertSame('png', $mapped['format']);
        $this->assertSame(7, $mapped['png_compression_level']);
        $this->assertSame(30, $mapped['quality']);
        $this->assertSame('none', $mapped['interlace']);
    }

    public function testUnknownSerializableOptionsAndListOrdering(): void
    {
        $a = (new EncodingOptions())->withAdapterOptions('imagine', ['z' => [2, 1], 'a' => ['b' => 2, 'a' => 1]]);
        $b = (new EncodingOptions())->withAdapterOptions('imagine', ['a' => ['a' => 1, 'b' => 2], 'z' => [2, 1]]);
        $this->assertSame($a->toArray(), $b->toArray());
        $this->assertSame([2, 1], $a->getAdapterOptions('imagine')['z']);
    }

    public function testUnsupportedEncoderRetainsCause(): void
    {
        try {
            $this->factory->getResizer()->resize($this->source(), (new ResizeConfiguration())->withWidth(60), (new ProcessingOptions())->withEncoding((new EncodingOptions())->withFormat('unsupported')));
            $this->fail('Expected encoding error');
        } catch (ImageEncodingException $exception) {
            $this->assertNotNull($exception->getPrevious());
        }
    }
}
