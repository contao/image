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

use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Contao\ImagineSvg\SvgBox;
use Contao\ImagineSvg\UndefinedBox;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ResizerTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = Path::canonicalize(__DIR__.'/tmp');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResize(bool $withSecret): void
    {
        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer($withSecret, null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $defaultUmask = umask();

        try {
            foreach ([0000, 0002, 0007, 0022, 0027, 0077] as $umask) {
                umask($umask);

                $resizedImage = $resizer->resize(
                    $image,
                    $configuration,
                    (new ResizeOptions())
                        ->setImagineOptions([
                            'jpeg_quality' => 95,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                            'jpeg_sampling_factors' => [2, 1, 1],
                        ])
                );

                $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());

                if ($withSecret) {
                    $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.jpg$)', $resizedImage->getPath());
                } else {
                    $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
                }
                $this->assertFilePermissions(0666, $resizedImage->getPath());

                unlink($resizedImage->getPath());
            }
        } finally {
            umask($defaultUmask);
        }

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        // Replace target image with larger image
        (new GdImagine())
            ->create(new Box(200, 200))
            ->save(Path::join($this->rootDir, 'target-path.jpg'))
        ;

        // Resize with override
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
        );

        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertFilePermissions(0666, $resizedImage->getPath());
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeSvg(bool $withSecret): void
    {
        $xml = '<?xml version="1.0"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100" height="100"></svg>';

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents(Path::join($this->rootDir, 'dummy.svg'), $xml);

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer($withSecret, null, $calculator);

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.svg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new SvgImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setImagineOptions([
                    'jpeg_quality' => 95,
                    'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                    'jpeg_sampling_factors' => [2, 1, 1],
                ])
        );

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());
        $this->assertFalse($resizedImage->getDimensions()->isUndefined());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.svg)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());
        }

        unlink($resizedImage->getPath());

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setTargetPath(Path::join($this->rootDir, 'target-path.svg'))
        );

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());
        $this->assertFalse($resizedImage->getDimensions()->isUndefined());
        $this->assertSame(Path::join($this->rootDir, 'target-path.svg'), $resizedImage->getPath());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        unlink($resizedImage->getPath());
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeCache(bool $withSecret): void
    {
        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer($withSecret, null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.jpg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertFilePermissions(0666, $resizedImage->getPath());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.jpg$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        }

        $imagePath = $resizedImage->getPath();

        // Different cache file for testing
        (new GdImagine())
            ->create(new Box(200, 100))
            ->save($imagePath)
        ;

        // With cache
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame($imagePath, $resizedImage->getPath());
        $this->assertSame(200, getimagesize($imagePath)[0], 'Cache file should no be overwritten');

        // With cache and target path
        $targetPath = Path::join($this->rootDir, 'target-image.jpg');
        $resizedImage = $resizer->resize($image, $configuration, (new ResizeOptions())->setTargetPath($targetPath));

        $this->assertSame($targetPath, $resizedImage->getPath());
        $this->assertFileEquals($imagePath, $targetPath, 'Cache file should have been copied');

        // With different imagine options
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setImagineOptions(['jpeg_quality' => 10])
        );

        $this->assertNotSame($imagePath, $resizedImage->getPath());
        $this->assertSame(100, getimagesize($resizedImage->getPath())[0], 'New cache file should have been created');

        // With different paths, but same relative path
        $subDir = Path::join($this->rootDir, 'sub/dir');

        mkdir($subDir, 0777, true);
        copy(Path::join($this->rootDir, 'dummy.jpg'), Path::join($subDir, 'dummy.jpg'));
        touch(Path::join($subDir, 'dummy.jpg'), filemtime(Path::join($this->rootDir, 'dummy.jpg')));

        $subResizer = $this->createResizer($withSecret, $subDir, $calculator);

        $subImage = $this->createMock(Image::class);
        $subImage
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $subImage
            ->method('getPath')
            ->willReturn(Path::join($subDir, 'dummy.jpg'))
        ;

        $subImage
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $resizedImage = $subResizer->resize($subImage, $configuration, (new ResizeOptions())->setBypassCache(true));

        $this->assertSame(
            substr($imagePath, \strlen($this->rootDir)),
            substr($resizedImage->getPath(), \strlen($subDir)),
            'The hash should be the same if the image path relative to the cacheDir is the same'
        );

        // Without cache
        $resizedImage = $resizer->resize($image, $configuration, (new ResizeOptions())->setBypassCache(true));

        $this->assertSame($imagePath, $resizedImage->getPath());
        $this->assertSame(100, getimagesize($imagePath)[0]);
    }

    public function testResizeUndefinedSize(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(
                class_exists(SvgBox::class) ? SvgBox::createTypeNone() : new UndefinedBox()
            ))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertSame($imagePath, $resizedImage->getPath());

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame($imagePath, $resizedImage->getPath());
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeEmptyConfig(bool $withSecret): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer($withSecret);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.jpg$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        }

        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeEmptyConfigSkipsMatchingDimensions(): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer();

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents($imagePath, '');

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertSame($image->getPath(), $resizedImage->getPath());
        $this->assertNotSame($image, $resizedImage);
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeEmptyConfigWithFormat(bool $withSecret): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer($withSecret);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setSkipIfDimensionsMatch(true)
                ->setImagineOptions(['format' => 'png'])
        );

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.png$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.png$)', $resizedImage->getPath());
        }

        $this->assertNotSame($image, $resizedImage);
    }

    public function testResizeSameDimensions(): void
    {
        $path = Path::join($this->rootDir, 'dummy.jpg');

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer(true, null, $calculator);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($path)
        ;

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($path)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame($path, $resizedImage->getPath());

        $resizedImage = $resizer->resize(
            $image,
            $configuration,
            (new ResizeOptions())
                ->setTargetPath(Path::join($this->rootDir, 'target-path.jpg'))
                ->setSkipIfDimensionsMatch(true)
        );

        $this->assertEquals(new ImageDimensions(new Box(100, 100)), $resizedImage->getDimensions());
        $this->assertSame(Path::join($this->rootDir, 'target-path.jpg'), $resizedImage->getPath());
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeSameDimensionsRelative(bool $withSecret): void
    {
        $xml = '<?xml version="1.0"?>'.
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 100 100"></svg>';

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        file_put_contents(Path::join($this->rootDir, 'dummy.svg'), $xml);

        $calculator = $this->createMock(ResizeCalculator::class);
        $calculator
            ->method('calculate')
            ->willReturn(new ResizeCoordinates(new Box(100, 100), new Point(0, 0), new Box(100, 100)))
        ;

        $resizer = $this->createResizer($withSecret, null, $calculator);

        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100), true))
        ;

        $image
            ->method('getPath')
            ->willReturn(Path::join($this->rootDir, 'dummy.svg'))
        ;

        $image
            ->method('getImagine')
            ->willReturn(new SvgImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $resizedImage->getDimensions()->getSize()->getHeight());
        $this->assertFalse($resizedImage->getDimensions()->isRelative());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.svg$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.svg$)', $resizedImage->getPath());
        }

        unlink($resizedImage->getPath());
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeEmptyConfigRotatedImage(bool $withSecret): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer($withSecret);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100), null, null, ImageDimensions::ORIENTATION_180))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.jpg$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        }

        $this->assertNotSame($image, $resizedImage);
    }

    /**
     * @dataProvider getWithOrWithoutSecret
     */
    public function testResizeEmptyConfigNoSkip(bool $withSecret): void
    {
        $imagePath = Path::join($this->rootDir, 'dummy.jpg');
        $resizer = $this->createResizer($withSecret);

        if (!is_dir($this->rootDir)) {
            mkdir($this->rootDir, 0777, true);
        }

        (new GdImagine())
            ->create(new Box(100, 100))
            ->save($imagePath)
        ;

        /** @var Image|MockObject $image */
        $image = $this->createMock(Image::class);
        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)))
        ;

        $image
            ->method('getPath')
            ->willReturn($imagePath)
        ;

        $image
            ->method('getImagine')
            ->willReturn(new GdImagine())
        ;

        $configuration = $this->createMock(ResizeConfiguration::class);
        $configuration
            ->method('isEmpty')
            ->willReturn(true)
        ;

        $resizedImage = $resizer->resize($image, $configuration, new ResizeOptions());

        if ($withSecret) {
            $this->assertMatchesRegularExpression('(/[0-9a-z]/dummy-[0-9a-z]{15}.jpg$)', $resizedImage->getPath());
        } else {
            $this->assertMatchesRegularExpression('(/[0-9a-f]/dummy-[0-9a-f]{8}.jpg$)', $resizedImage->getPath());
        }

        $this->assertNotSame($image, $resizedImage);
    }

    public function getWithOrWithoutSecret(): \Generator
    {
        yield [true];
        yield [false];
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }

    private function createResizer(bool $withSecret = true, string $cacheDir = null, ResizeCalculator $calculator = null, Filesystem $filesystem = null): Resizer
    {
        if (null === $cacheDir) {
            $cacheDir = $this->rootDir;
        }

        if ($withSecret) {
            return new Resizer($cacheDir, 'secret', $calculator, $filesystem);
        }

        $this->expectDeprecation('Not passing a secret%s');

        return new Resizer($cacheDir, $calculator, $filesystem);
    }

    private function assertFilePermissions(int $expectedPermissions, string $path): void
    {
        $this->assertSame(
            sprintf('%o', $expectedPermissions & ~umask() & 0777),
            sprintf('%o', fileperms($path) & 0777)
        );
    }
}
