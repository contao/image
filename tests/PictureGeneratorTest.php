<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\ImageInterface;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizerInterface;
use Contao\Image\ResizeOptions;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptionsInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Imagine\Image\Box;

/**
 * Tests the PictureGenerator class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a PictureGenerator instance helper.
     *
     * @param ResizerInterface $resizer
     *
     * @return PictureGenerator
     */
    private function createPictureGenerator($resizer = null, $bypassCache = null)
    {
        if (null === $resizer) {
            $resizer = $this->getMockBuilder('Contao\Image\ResizerInterface')
             ->disableOriginalConstructor()
             ->getMock();
        }

        if (null === $bypassCache) {
            $bypassCache = false;
        }

        return new PictureGenerator($resizer, $bypassCache);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\Image\PictureGenerator', $this->createPictureGenerator());
        $this->assertInstanceOf('Contao\Image\PictureGeneratorInterface', $this->createPictureGenerator());
    }

    /**
     * Tests the create() method.
     */
    public function testGenerate()
    {
        $resizer = $this->getMockBuilder('Contao\Image\ResizerInterface')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
             ->expects($this->any())
             ->method('resize')
             ->will($this->returnCallback(function (ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options) {
                 $imageMock = $this->getMockBuilder('Contao\Image\Image')
                      ->disableOriginalConstructor()
                      ->getMock();

                 $imageMock
                     ->expects($this->any())
                     ->method('getDimensions')
                     ->willReturn(new ImageDimensions(new Box($config->getWidth(), $config->getHeight())));

                 $imageMock
                     ->expects($this->any())
                     ->method('getUrl')
                     ->willReturn('image-' . $config->getWidth() . '.jpg');

                 return $imageMock;
             }));

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
              ->disableOriginalConstructor()
              ->getMock();

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('1x, 2x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(100)
                    ->setHeight(100)
                )
            )
            ->setSizeItems([(new PictureConfigurationItem())
                ->setMedia('(min-width: 600px)')
                ->setDensities('0.5x')
                ->setSizes('50vw')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(100)
                    ->setHeight(50)
                ),
            ]);

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertEquals([
            'src' => 'image-100.jpg',
            'width' => '100',
            'height' => '100',
            'srcset' => 'image-100.jpg 1x, image-200.jpg 2x',
        ], $picture->getImg('/root/dir'));

        $this->assertEquals([[
            'src' => 'image-100.jpg',
            'width' => '100',
            'height' => '50',
            'srcset' => 'image-100.jpg 100w, image-50.jpg 50w',
            'sizes' => '50vw',
            'media' => '(min-width: 600px)',
        ]], $picture->getSources('/root/dir'));

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    /**
     * Tests the create() method.
     */
    public function testGenerateWDescriptor()
    {
        $resizer = $this->getMockBuilder('Contao\Image\ResizerInterface')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->any())
            ->method('resize')
            ->will($this->returnCallback(function (ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options) {
                $imageMock = $this->getMockBuilder('Contao\Image\Image')
                     ->disableOriginalConstructor()
                     ->getMock();

                $imageMock
                    ->expects($this->any())
                    ->method('getDimensions')
                    ->willReturn(new ImageDimensions(new Box(($config->getHeight() * 2), $config->getHeight())));

                $imageMock
                    ->expects($this->any())
                    ->method('getUrl')
                    ->willReturn('image-' . ($config->getHeight() * 2) . '.jpg');

                return $imageMock;
            }));

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setDensities('200w, 400w, 0.5x')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setHeight(100)
                )
            )
            ->setSizeItems([(new PictureConfigurationItem())
                ->setDensities('1x, 100w, 200w, 0.5x')
                ->setSizes('33vw')
                ->setResizeConfig((new ResizeConfiguration())
                    ->setWidth(100)
                    ->setHeight(50)
                ),
            ]);

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertEquals([
            'src' => 'image-200.jpg',
            'width' => '200',
            'height' => '100',
            'srcset' => 'image-200.jpg 200w, image-400.jpg 400w, image-100.jpg 100w',
            'sizes' => '100vw',
        ], $picture->getImg('/root/dir'));

        $this->assertEquals([[
            'src' => 'image-100.jpg',
            'width' => '100',
            'height' => '50',
            'srcset' => 'image-100.jpg 100w, image-200.jpg 200w, image-50.jpg 50w',
            'sizes' => '33vw',
        ]], $picture->getSources('/root/dir'));

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }
}
