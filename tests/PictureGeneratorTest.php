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
use Contao\Image\Resizer;
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
     * @var string
     */
    private $rootDir = '/root/dir';

    /**
     * Create a PictureGenerator instance helper.
     *
     * @param Resizer $resizer
     *
     * @return PictureGenerator
     */
    private function createPictureGenerator($resizer = null, $bypassCache = null, $rootDir = null)
    {
        if (null === $resizer) {
            $resizer = $this->getMockBuilder('Contao\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();
        }

        if (null === $bypassCache) {
            $bypassCache = false;
        }

        if (null === $rootDir) {
            $rootDir = $this->rootDir;
        }

        return new PictureGenerator($resizer, $bypassCache, $rootDir);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\Image\PictureGenerator', $this->createPictureGenerator());
    }

    /**
     * Tests the create() method.
     */
    public function testGenerate()
    {
        $path = $this->rootDir . '/images/dummy.jpg';

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
            ->willReturn($this->rootDir . '/path/to/image.jpg');

        $resizer = $this->getMockBuilder('Contao\Image\Resizer')
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

        $picture = $pictureGenerator->generate($imageMock, $pictureConfig);

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
    }
}
