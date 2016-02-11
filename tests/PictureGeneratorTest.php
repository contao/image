<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Image;

use Contao\CoreBundle\Test\TestCase;
use Contao\CoreBundle\Image\PictureGenerator;
use Contao\CoreBundle\Image\Resizer;
use Contao\CoreBundle\Image\ImageDimensions;
use Contao\CoreBundle\Image\ResizeConfiguration;
use Contao\CoreBundle\Image\PictureConfiguration;
use Contao\CoreBundle\Image\PictureConfigurationItem;
use Imagine\Image\Box;

/**
 * Tests the PictureGenerator class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureGeneratorTest extends TestCase
{
    /**
     * Create a PictureGenerator instance helper.
     *
     * @param Resizer $resizer
     *
     * @return PictureGenerator
     */
    private function createPictureGenerator($resizer = null, $bypassCache = null)
    {
        if (null === $resizer) {
            $resizer = $this->getMockBuilder('Contao\CoreBundle\Image\Resizer')
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
        $this->assertInstanceOf('Contao\CoreBundle\Image\PictureGenerator', $this->createPictureGenerator());
    }

    /**
     * Tests the create() method.
     */
    public function testGenerate()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $imageMock
            ->expects($this->any())
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(100, 100)));

        $imageMock
            ->expects($this->any())
            ->method('getPath')
            ->willReturn('path/to/image.jpg');

        $resizer = $this->getMockBuilder('Contao\CoreBundle\Image\Resizer')
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

        $this->assertInstanceOf('Contao\\CoreBundle\\Image\\Picture', $picture);
    }
}
