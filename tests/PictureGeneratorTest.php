<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\PictureGenerator;
use Contao\Image\ResizerInterface;
use Contao\Image\ResizeOptions;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeConfiguration;
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
        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $imageMock
            ->expects($this->any())
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)));

        $imageMock
            ->expects($this->any())
            ->method('getPath')
            ->willReturn('/path/to/image.jpg');

        $imageMock
            ->expects($this->any())
            ->method('getUrl')
            ->willReturn('image.jpg');

        $resizer = $this->getMockBuilder('Contao\Image\ResizerInterface')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->any())
            ->method('resize')
            ->willReturn($imageMock);

        $pictureGenerator = $this->createPictureGenerator($resizer);

        $pictureConfig = new PictureConfiguration();
        $pictureItem = new PictureConfigurationItem();
        $pictureItem->setMedia('(min-width: 600px)');
        $pictureItem->setDensities('1x, 2x');
        $pictureItem->setSizes('100vw');
        $resizeConfig = new ResizeConfiguration();
        $resizeConfig->setWidth(100)->setHeight(100);
        $pictureItem->setResizeConfig($resizeConfig);
        $pictureConfig->setSize($pictureItem);
        $pictureConfig->setSizeItems([$pictureItem]);

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig, new ResizeOptions());

        $this->assertEquals([
            'src' => 'image.jpg',
            'width' => '100',
            'height' => '100',
            'srcset' => 'image.jpg 100w, image.jpg 100w',
            'sizes' => '100vw',
            'media' => '(min-width: 600px)',
        ], $picture->getImg('/root/dir'));

        $this->assertEquals([[
            'src' => 'image.jpg',
            'width' => '100',
            'height' => '100',
            'srcset' => 'image.jpg 100w, image.jpg 100w',
            'sizes' => '100vw',
            'media' => '(min-width: 600px)',
        ]], $picture->getSources('/root/dir'));

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }
}
