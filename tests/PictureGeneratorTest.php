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
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Imagine\Image\Box;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PictureGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options) {
                    $format = $options->getImagineOptions()['format'];

                    $imageMock = $this->createMock(Image::class);
                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box(
                            min(1000, $config->getWidth()),
                            min(1000, $config->getHeight())
                        )))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.min(1000, $config->getWidth()).'.'.$format)
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.min(1000, $config->getWidth()).'.'.$format)
                    ;

                    $this->assertContains($format, ['jpg', 'webp']);

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(1000, 1000)))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $imageMock
            ->method('getPath')
            ->willReturn('/dir/source-image.jpg')
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setFormats([
                'jpg' => ['webp', 'jpg'],
            ])
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('1x, 1.35354x, 1.9999x, 10x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(99)
                            ->setHeight(99)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 600px)')
                        ->setDensities('0.5x')
                        ->setSizes('50vw')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(100)
                                ->setHeight(50)
                        ),
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 300px)')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(50)
                                ->setHeight(25)
                        ),
                    (new PictureConfigurationItem())
                        ->setMedia('(min-width: 200px)')
                        ->setDensities('1x, 10x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(160)
                                ->setHeight(160)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-99.jpg 1x, image-134.jpg 1.354x, image-198.jpg 2x, image-990.jpg 10x',
                'src' => 'image-99.jpg',
                'width' => 99,
                'height' => 99,
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.webp 100w, image-50.webp 50w',
                    'src' => 'image-100.webp',
                    'width' => 100,
                    'height' => 50,
                    'sizes' => '50vw',
                    'media' => '(min-width: 600px)',
                    'type' => 'image/webp',
                ],
                [
                    'srcset' => 'image-100.jpg 100w, image-50.jpg 50w',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                    'sizes' => '50vw',
                    'media' => '(min-width: 600px)',
                ],
                [
                    'srcset' => 'image-50.webp',
                    'src' => 'image-50.webp',
                    'width' => 50,
                    'height' => 25,
                    'media' => '(min-width: 300px)',
                    'type' => 'image/webp',
                ],
                [
                    'srcset' => 'image-50.jpg',
                    'src' => 'image-50.jpg',
                    'width' => 50,
                    'height' => 25,
                    'media' => '(min-width: 300px)',
                ],
                [
                    'srcset' => 'image-160.webp 1x, image-1000.webp 6.25x',
                    'src' => 'image-160.webp',
                    'width' => 160,
                    'height' => 160,
                    'media' => '(min-width: 200px)',
                    'type' => 'image/webp',
                ],
                [
                    'srcset' => 'image-160.jpg 1x, image-1000.jpg 6.25x',
                    'src' => 'image-160.jpg',
                    'width' => 160,
                    'height' => 160,
                    'media' => '(min-width: 200px)',
                ],
                [
                    'srcset' => 'image-99.webp 1x, image-134.webp 1.354x, image-198.webp 2x, image-990.webp 10x',
                    'src' => 'image-99.webp',
                    'width' => 99,
                    'height' => 99,
                    'type' => 'image/webp',
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateWDescriptor(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options) {
                    $imageMock = $this->createMock(Image::class);
                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box($config->getHeight() * 2, $config->getHeight())))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.($config->getHeight() * 2).'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.($config->getHeight() * 2).'.jpg')
                    ;

                    $this->assertSame('', $options->getImagineOptions()['format']);

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(400, 200)))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $imageMock
            ->method('getPath')
            ->willReturn('/dir/source-image-without-extension')
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('200w, 400w, 0.5x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setHeight(100)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 100w, 200w, 0.5x')
                        ->setSizes('33vw')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(100)
                                ->setHeight(50)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-200.jpg 200w, image-400.jpg 400w, image-100.jpg 100w',
                'src' => 'image-200.jpg',
                'width' => 200,
                'height' => 100,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.jpg 100w, image-200.jpg 200w, image-50.jpg 50w',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                    'sizes' => '33vw',
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateWDescriptorSmallImage(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config) {
                    $imageMock = $this->createMock(Image::class);

                    $calculator = new ResizeCalculator();
                    $size = $calculator->calculate($config, new ImageDimensions(new Box(123, 246)))->getCropSize();

                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions($size))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.$size->getWidth().'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.$size->getWidth().'.jpg')
                    ;

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(123, 246)))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(200)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setHeight(400)
                        ),
                    (new PictureConfigurationItem())
                        ->setDensities('0.5x, 0.25x, 0.2x, 2x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(200)
                        ),
                    (new PictureConfigurationItem())
                        ->setDensities('100w, 50w, 0.2x, 500w, 2x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(200)
                                ->setHeight(440)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-123.jpg 123w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                'src' => 'image-123.jpg',
                'width' => 123,
                'height' => 246,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-123.jpg 123w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                    'src' => 'image-123.jpg',
                    'width' => 123,
                    'height' => 246,
                    'sizes' => '100vw',
                ],
                [
                    'srcset' => 'image-123.jpg 0.615x, image-100.jpg 0.5x, image-50.jpg 0.25x, image-40.jpg 0.2x',
                    'src' => 'image-123.jpg',
                    'width' => 123,
                    'height' => 246,
                ],
                [
                    'srcset' => 'image-112.jpg 112w, image-100.jpg 100w, image-50.jpg 50w, image-40.jpg 40w',
                    'src' => 'image-112.jpg',
                    'width' => 112,
                    'height' => 246,
                    'sizes' => '100vw',
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateDuplicateSrcsetItems(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config) {
                    $imageMock = $this->createMock(Image::class);
                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box(
                            min(200, $config->getWidth()),
                            min(200, $config->getHeight())
                        )))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.min(200, $config->getWidth()).'.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.min(200, $config->getWidth()).'.jpg')
                    ;

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('200w, 400w, 600w, 3x, 4x, 0.5x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(100)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 2x, 3x, 4x, 0.5x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(100)
                                ->setHeight(50)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-100.jpg 100w, image-200.jpg 200w, image-50.jpg 50w',
                'src' => 'image-100.jpg',
                'width' => 100,
                'height' => 100,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.jpg 1x, image-200.jpg 2x, image-50.jpg 0.5x',
                    'src' => 'image-100.jpg',
                    'width' => 100,
                    'height' => 50,
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateSmallSourceImage(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config) {
                    $imageMock = $this->createMock(Image::class);
                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box(20, 20)))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-20.jpg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-20.jpg')
                    ;

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(20, 20)))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('200w, 400w, 600w, 3x, 4x, 0.5x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(100)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 2x, 3x, 4x, 0.5x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(100)
                                ->setHeight(100)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-20.jpg 20w',
                'src' => 'image-20.jpg',
                'width' => 20,
                'height' => 20,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-20.jpg',
                    'src' => 'image-20.jpg',
                    'width' => 20,
                    'height' => 20,
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateSvgImage(): void
    {
        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config) {
                    $imageMock = $this->createMock(Image::class);
                    $imageMock
                        ->method('getDimensions')
                        ->willReturn(new ImageDimensions(new Box($config->getWidth(), $config->getHeight())))
                    ;

                    $imageMock
                        ->method('getUrl')
                        ->willReturn('image-'.$config->getWidth().'.svg')
                    ;

                    $imageMock
                        ->method('getPath')
                        ->willReturn('/dir/image-'.$config->getWidth().'.svg')
                    ;

                    return $imageMock;
                }
            )
        ;

        $imageMock = $this->createMock(Image::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(200, 200)))
        ;

        $imageMock
            ->method('getImagine')
            ->willReturn($this->createMock(ImagineSvg::class))
        ;

        $imageMock
            ->method('getImportantPart')
            ->willReturn(new ImportantPart())
        ;

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setDensities('200w, 400w, 600w, 3x, 4x, 0.5x')
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(100)
                    )
            )
            ->setSizeItems(
                [
                    (new PictureConfigurationItem())
                        ->setDensities('1x, 2x, 3x, 4x, 0.5x')
                        ->setResizeConfig(
                            (new ResizeConfiguration())
                                ->setWidth(100)
                                ->setHeight(50)
                        ),
                ]
            )
        ;

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertSame(
            [
                'srcset' => 'image-100.svg 100w',
                'src' => 'image-100.svg',
                'width' => 100,
                'height' => 100,
                'sizes' => '100vw',
            ],
            $picture->getImg('/root/dir')
        );

        $this->assertSame(
            [
                [
                    'srcset' => 'image-100.svg',
                    'src' => 'image-100.svg',
                    'width' => 100,
                    'height' => 50,
                ],
            ],
            $picture->getSources('/root/dir')
        );
    }

    public function testGenerateWithLocale(): void
    {
        $locale = setlocale(LC_NUMERIC, '0');

        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        try {
            $requiredLocales = ['de_DE.UTF-8', 'de_DE.UTF8', 'de_DE.utf-8', 'de_DE.utf8', 'German_Germany.1252'];

            if (false === setlocale(LC_NUMERIC, $requiredLocales)) {
                $this->markTestSkipped('Could not set any of required locales: '.implode(', ', $requiredLocales));
            }

            $this->testGenerate();
        } finally {
            setlocale(LC_NUMERIC, $locale);
        }
    }

    /**
     * @param ResizerInterface&MockObject $resizer
     */
    private function createPictureGenerator(ResizerInterface $resizer = null): PictureGenerator
    {
        if (null === $resizer) {
            $resizer = $this->createMock(ResizerInterface::class);
        }

        return new PictureGenerator($resizer);
    }
}
