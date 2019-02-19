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
use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageDimensionsInterface;
use Contao\Image\ImportantPartInterface;
use Contao\Image\ResizeCalculatorInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

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

    public function testInstantiation(): void
    {
        $resizer = $this->createResizer();

        $this->assertInstanceOf('Contao\Image\DeferredResizer', $resizer);
        $this->assertInstanceOf('Contao\Image\DeferredResizerInterface', $resizer);
        $this->assertInstanceOf('Contao\Image\ResizerInterface', $resizer);
    }

    public function testResize(): void
    {
        $calculator = $this->createMock(ResizeCalculatorInterface::class);
        $calculator
            ->method('calculate')
            ->willReturnCallback(function (ResizeConfigurationInterface $config, ImageDimensionsInterface $dimensions, ImportantPartInterface $importantPart = null) {
                return new ResizeCoordinates(new Box($config->getWidth(), $config->getHeight()), new Point(0, 0), new Box($config->getWidth(), $config->getHeight()));
            })
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

        $defaultUmask = umask();

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
        $this->assertFileExists($deferredImage->getPath().'.config');

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

        $resizedImage = $resizer->resizeDeferredImage($deferredImage2);

        $this->assertNotInstanceOf(DeferredImageInterface::class, $resizedImage);
        $this->assertEquals(new ImageDimensions(new Box(50, 50)), $resizedImage->getDimensions());
        $this->assertFileExists($resizedImage->getPath());
        $this->assertFileNotExists($resizedImage->getPath().'.config');

        $resizedImage = $resizer->resize(
            $image,
            (new ResizeConfiguration())
                ->setWidth(100)
                ->setHeight(100),
            (new ResizeOptions())->setTargetPath($this->rootDir.'/target-path.jpg')
        );

        $this->assertNotInstanceOf(DeferredImageInterface::class, $resizedImage);
        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame($this->rootDir.'/target-path.jpg', $resizedImage->getPath());
        $this->assertFileExists($resizedImage->getPath());
    }

    /**
     * Returns a resizer.
     *
     * @param string                    $cacheDir
     * @param ResizeCalculatorInterface $calculator
     * @param Filesystem                $filesystem
     *
     * @return DeferredResizer
     */
    private function createResizer($cacheDir = null, $calculator = null, $filesystem = null)
    {
        if (null === $cacheDir) {
            $cacheDir = $this->rootDir;
        }

        return new DeferredResizer($cacheDir, $calculator, $filesystem);
    }

    /**
     * @param int    $expectedPermissions
     * @param string $path
     */
    private function assertFilePermissions($expectedPermissions, $path): void
    {
        $this->assertSame(
            sprintf('%o', $expectedPermissions & ~umask() & 0777),
            sprintf('%o', fileperms($path) & 0777)
        );
    }
}
