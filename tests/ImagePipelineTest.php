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
use Contao\Image\Exception\SourceVersionMismatchException;
use Contao\Image\FilesystemImageConfiguration;
use Contao\Image\FilesystemImageFactory;
use Contao\Image\FilesystemSourceResolver;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalImageResultInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Imagine\Gd\Imagine;
use Symfony\Component\Filesystem\Filesystem;

class ImagePipelineTest extends AbstractImagePipelineTest
{
    public function testImmediateAndPersistentDeferredResults(): void
    {
        $source = $this->source();
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $result = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withDeferred(true));
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertSame([60, 40], $result->getDimensions()->getSize()->toArray());
        $this->assertFileDoesNotExist($result->getPath());
        $this->assertCount(1, iterator_to_array($this->factory->getRecords()->listPending()));
        $done = $this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity());
        $this->assertInstanceOf(LocalImageResultInterface::class, $done);
        $this->assertSame([60, 40], \array_slice(getimagesize($done->getPath()), 0, 2));
        $this->assertCount(0, iterator_to_array($this->factory->getRecords()->listPending()));
        $this->assertCount(1, iterator_to_array($this->factory->getRecords()->listCompleted()));
        $this->assertNull($this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity(), false));
        $this->assertSame($done->getUrl(), $this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity())->getUrl());
    }

    public function testWarmResultTrustAndExplicitRecovery(): void
    {
        $source = $this->source();
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $result = $this->factory->getResizer()->resize($source, $configuration, new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        (new Filesystem())->remove($result->getPath());
        $warm = $this->factory->getResizer()->resize($source, $configuration, new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $warm);
        $this->assertSame($result->getIdentity()->getPath(), $warm->getIdentity()->getPath());
        $this->assertFileDoesNotExist($warm->getPath());
        $this->factory->getDeferredProcessor()->invalidate($result->getIdentity());
        $this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity());
        $this->assertFileExists($result->getPath());
    }

    public function testVersionMismatchLeavesPendingRecordAndNoContent(): void
    {
        $source = $this->source();
        $result = $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), (new ProcessingOptions())->withDeferred(true));
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        (new Filesystem())->dumpFile($this->directory.'/source', 'changed');

        try {
            $this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity());
            $this->fail('Expected mismatch');
        } catch (SourceVersionMismatchException) {
            $this->assertFileDoesNotExist($result->getPath());
        }
        $this->assertCount(1, iterator_to_array($this->factory->getRecords()->listPending()));
    }

    public function testCopyPublicUrlsAndDynamicTargetUrls(): void
    {
        $source = $this->source();
        $options = (new ProcessingOptions())->withSkipIfDimensionsMatch(true);
        $copy = $this->factory->getResizer()->resize($source, new ResizeConfiguration(), $options);
        $this->assertInstanceOf(LocalImageResultInterface::class, $copy);
        $this->assertSame(file_get_contents($this->directory.'/source'), file_get_contents($copy->getPath()));
        $resolver = new FilesystemSourceResolver(['assets' => $this->directory], ['assets' => 'https://assets.example']);
        $factory = new FilesystemImageFactory(new FilesystemImageConfiguration($this->directory.'/cache', '/new-images', 'test-secret'), $resolver, new ImagineImageDecoderEncoder(new Imagine()));
        $public = $factory->getResizer()->resize($resolver->resolve(new SourceReference('assets', 'source')), new ResizeConfiguration(), $options);
        $this->assertSame('https://assets.example/source', $public->getUrl());
        $updated = $factory->getDeferredProcessor()->getResult($copy->getIdentity());
        $this->assertStringStartsWith('/new-images/', $updated->getUrl());
        $this->assertSame($copy->getIdentity()->getPath(), $updated->getIdentity()->getPath());
    }

    public function testChainingBypassAndExports(): void
    {
        $source = $this->source();
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $parent = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withDeferred(true));
        $this->assertInstanceOf(LocalImageResultInterface::class, $parent);
        $child = $this->factory->getResizer()->resize($parent->asSource(), (new ResizeConfiguration())->withWidth(30), new ProcessingOptions());
        $this->assertInstanceOf(LocalImageResultInterface::class, $child);
        $this->assertFileExists($parent->getPath());
        $this->assertSame([30, 20], \array_slice(getimagesize($child->getPath()), 0, 2));
        $revision = $parent->asSource()->getVersion();
        $replacement = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withBypassCache(true));
        $this->assertInstanceOf(LocalImageResultInterface::class, $replacement);
        $this->assertSame($parent->getIdentity()->getPath(), $replacement->getIdentity()->getPath());
        $this->assertNotSame($revision, $replacement->asSource()->getVersion());
        $export = $this->factory->getResizer()->resize($source, $configuration, (new ProcessingOptions())->withDeferred(true)->withDestination($this->directory.'/export.jpg'));
        $this->assertInstanceOf(LocalImageResultInterface::class, $export);
        $this->assertSame($this->directory.'/export.jpg', $export->getPath());
        $this->assertFileExists($export->getPath());
        $this->factory->getLifecycle()->notifyDeleted($source->getReference());
        $this->factory->getLifecycle()->prune(new \DateInterval('PT0S'));
        $this->assertFileExists($export->getPath());
    }

    public function testPictureOrderingAndDescriptors(): void
    {
        $source = $this->source();
        $configuration = (new PictureConfiguration())->withSize((new PictureConfigurationItem())->withResizeConfig((new ResizeConfiguration())->withWidth(40))->withDensities('1x,2x,3x,4x'));
        $picture = $this->factory->getPictureGenerator()->generate($source, $configuration, (new ProcessingOptions())->withDeferred(true));
        $this->assertSame(40, $picture->getImg()['width']);
        $this->assertSame(27, $picture->getImg()['height']);
        $this->assertCount(3, $picture->getRawImg()['srcset']);
        $this->assertStringContainsString('3x', $picture->getImg()['srcset']);
    }

    public function testEncodingConversion(): void
    {
        $result = $this->factory->getResizer()->resize($this->source(), (new ResizeConfiguration())->withWidth(60), (new ProcessingOptions())->withEncoding((new EncodingOptions())->withFormat('png')->withPngCompression(9)));
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $this->assertSame('png', $result->getFormat());
        $this->assertSame(IMAGETYPE_PNG, getimagesize($result->getPath())[2]);
    }
}
