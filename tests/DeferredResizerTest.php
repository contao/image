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

use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredImageStorageInterface;
use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DeferredResizerTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testResize(): void
    {
        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturnCallback(
                static function (ResizeConfiguration $config, ImageDimensions $dimensions, ImportantPart $importantPart = null) {
                    return new ResizeCoordinates(
                        new Box($config->getWidth(), $config->getHeight()),
                        new Point(0, 0),
                        new Box($config->getWidth(), $config->getHeight())
                    );
                }
            )
        ;

        $resizer = $this->createResizer(null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($this->rootDir.'/dummy.jpg')
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn($this->rootDir.'/dummy.jpg')
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $deferredImage = $resizer->resize(
            $image,
            (new ResizeConfiguration())
                ->setWidth(100)
                ->setHeight(100),
            (new ResizeOptions())
                ->setImagineOptions([
                    'jpeg_quality' => 95,
                    'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                    'jpeg_sampling_factors' => [2, 1, 1],
                ])
        );

        $this->assertInstanceOf(DeferredImageInterface::class, $deferredImage);
        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $deferredImage->getDimensions());
        $this->assertRegExp('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $deferredImage->getPath());
        $this->assertFileNotExists($deferredImage->getPath());
        $this->assertFileExists(
            $this->rootDir.'/deferred/'.substr($deferredImage->getPath(), \strlen($this->rootDir)).'.json'
        );

        /** @var DeferredImageInterface $deferredImage2 */
        $deferredImage2 = $resizer->resize(
            $deferredImage,
            (new ResizeConfiguration())
                ->setWidth(50)
                ->setHeight(50),
            (new ResizeOptions())
                ->setImagineOptions([
                    'jpeg_quality' => 95,
                    'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                    'jpeg_sampling_factors' => [2, 1, 1],
                ])
        );

        $this->assertFileExists($deferredImage->getPath());

        $resizedImage = $resizer->resizeDeferredImage($deferredImage2, false);

        $this->assertNotInstanceOf(DeferredImageInterface::class, $resizedImage);
        $this->assertEquals(new ImageDimensions(new Box(50, 50)), $resizedImage->getDimensions());
        $this->assertFileExists($resizedImage->getPath());
        $this->assertFileNotExists(
            $this->rootDir.'/deferred/'.substr($deferredImage->getPath(), \strlen($this->rootDir)).'.json'
        );

        // Calling resizeDeferredImage() a second time should return the already
        // generated image to prevent race conditions.
        $resizedImage2 = $resizer->resizeDeferredImage($deferredImage2);

        $this->assertSame($resizedImage->getPath(), $resizedImage2->getPath());

        $this->assertNull(
            $resizer->resizeDeferredImage($deferredImage2, false),
            'Non-blocking deferred resize of an existing image should return null'
        );

        $resizedImage = $resizer->resize(
            $image,
            (new ResizeConfiguration())->setWidth(100)->setHeight(100),
            (new ResizeOptions())->setTargetPath($this->rootDir.'/target-path.jpg')
        );

        $this->assertNotInstanceOf(DeferredImageInterface::class, $resizedImage);
        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame($this->rootDir.'/target-path.jpg', $resizedImage->getPath());
        $this->assertFileExists($resizedImage->getPath());
    }

    public function testGetDeferredImage(): void
    {
        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('has')
            ->willReturn(true)
        ;

        $storage
            ->method('get')
            ->willReturn([
                'coordinates' => [
                    'crop' => [
                        'width' => 200,
                        'height' => 100,
                    ],
                ],
            ])
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $resizer = $this->createResizer(null, null, null, $storage);
        $imagePath = $this->rootDir.'/a/foo-5fc1c9f9.jpg';
        $deferredImage = $resizer->getDeferredImage($imagePath, $imagine);

        $this->assertInstanceOf(DeferredImageInterface::class, $deferredImage);
        $this->assertEquals(new ImageDimensions(new Box(200, 100)), $deferredImage->getDimensions());
        $this->assertSame($imagePath, $deferredImage->getPath());

        $deferredImage = $resizer->getDeferredImage('/not/inside/cache-path.jpg', $imagine);

        $this->assertNull($deferredImage);
    }

    public function testGetMissingDeferredImage(): void
    {
        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('has')
            ->willReturn(false)
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $resizer = $this->createResizer(null, null, null, $storage);
        $imagePath = $this->rootDir.'/a/foo-5fc1c9f9.jpg';

        $this->assertNull($resizer->getDeferredImage($imagePath, $imagine));
    }

    public function testResizeDeferredImageThrowsForOutsidePath(): void
    {
        $resizer = $this->createResizer();

        $deferredImage = $this->createMock(DeferredImageInterface::class);
        $deferredImage
            ->method('getPath')
            ->willReturn($this->rootDir.'/../foo.jpg')
        ;

        $this->expectException('InvalidArgumentException');
        $resizer->resizeDeferredImage($deferredImage);
    }

    public function testResizeDeferredImageDoesNotCatchStorageException(): void
    {
        $storageException = new \RuntimeException('From storage');

        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('getLocked')
            ->with('foo.jpg', true)
            ->willThrowException($storageException)
        ;

        $resizer = $this->createResizer(null, null, null, $storage);

        $deferredImage = $this->createMock(DeferredImageInterface::class);
        $deferredImage
            ->method('getPath')
            ->willReturn($this->rootDir.'/foo.jpg')
        ;

        $this->expectExceptionObject($storageException);
        $resizer->resizeDeferredImage($deferredImage);
    }

    public function testResizeDeferredImageReturnsNullForLockedNonBlockingResize(): void
    {
        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('getLocked')
            ->with('foo.jpg', false)
            ->willReturn(null)
        ;

        $resizer = $this->createResizer(null, null, null, $storage);

        $deferredImage = $this->createMock(DeferredImageInterface::class);
        $deferredImage
            ->method('getPath')
            ->willReturn($this->rootDir.'/foo.jpg')
        ;

        $this->assertNull($resizer->resizeDeferredImage($deferredImage, false));
    }

    public function testResizeDeferredImageThrowsForLockedBlockingResize(): void
    {
        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('getLocked')
            ->willReturn(null)
        ;

        $resizer = $this->createResizer(null, null, null, $storage);

        $deferredImage = $this->createMock(DeferredImageInterface::class);
        $deferredImage
            ->method('getPath')
            ->willReturn($this->rootDir.'/foo.jpg')
        ;

        $this->expectException(\RuntimeException::class);
        $resizer->resizeDeferredImage($deferredImage, true);
    }

    public function testResizeDeferredImageReleasesLockForFailedResize(): void
    {
        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getLocked')
            ->with('foo.jpg', true)
            ->willReturn([
                'path' => $this->rootDir.'/foo.jpg',
                'coordinates' => [
                    'size' => [
                        'width' => 100,
                        'height' => 100,
                    ],
                    'crop' => [
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'height' => 100,
                    ],
                ],
                'options' => [
                    'imagine_options' => [],
                ],
            ])
        ;

        $storage
            ->expects($this->once())
            ->method('releaseLock')
            ->with('foo.jpg')
        ;

        $resizer = $this->createResizer(null, null, null, $storage);

        $deferredImage = $this->createMock(DeferredImageInterface::class);
        $deferredImage
            ->method('getPath')
            ->willReturn($this->rootDir.'/foo.jpg')
        ;

        $this->expectException(\InvalidArgumentException::class);
        $resizer->resizeDeferredImage($deferredImage);
    }

    private function createResizer(string $cacheDir = null, ResizeCalculator $calculator = null, Filesystem $filesystem = null, DeferredImageStorageInterface $storage = null): DeferredResizer
    {
        if (null === $cacheDir) {
            $cacheDir = $this->rootDir;
        }

        return new DeferredResizer($cacheDir, $calculator, $filesystem, $storage);
    }
}
