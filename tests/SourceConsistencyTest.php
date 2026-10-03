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

use Contao\Image\Exception\SourceVersionMismatchException;
use Contao\Image\FilesystemTargetContentStorage;
use Contao\Image\ImageDecoderEncoderInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\ImageTransformation;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalStoredImageBackend;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\Tests\Fixture\MemoryResolver;
use Contao\Image\Tests\Fixture\MemorySource;
use Contao\Image\Tests\Fixture\Pipeline;
use Imagine\Gd\Imagine;

class SourceConsistencyTest extends AbstractImagePipelineTest
{
    public function testMutationDuringEncodingDoesNotPublish(): void
    {
        $this->source();
        $source = new MemorySource(file_get_contents($this->directory.'/source'));
        $codec = new class() implements ImageDecoderEncoderInterface {
            public function transform(ImageSourceInterface $source, ImageTransformation $transformation)
            {
                $output = (new ImagineImageDecoderEncoder(new Imagine()))->transform($source, $transformation);

                if ($source instanceof MemorySource) {
                    $source->contents .= 'changed';
                }

                return $output;
            }
        };
        $resolver = new MemoryResolver($source);
        $pipeline = new Pipeline($this->directory.'/race', $resolver, new LocalStoredImageBackend($codec, new FilesystemTargetContentStorage($this->directory.'/content', '/images')));

        try {
            $pipeline->resizer->resize($source, (new ResizeConfiguration())->withWidth(60), new ProcessingOptions());
            $this->fail('Expected source mismatch');
        } catch (SourceVersionMismatchException) {
            $this->assertDirectoryDoesNotExist($this->directory.'/content');
        }
        $this->assertCount(1, iterator_to_array($pipeline->records->listPending()));
        $this->assertSame($source->opens, $source->closes);
    }
}
