<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Tests;

use Contao\Image\PictureConfigurationItem;

/**
 * Tests the PictureConfigurationItem class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureConfigurationItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $resizeConfig = new PictureConfigurationItem();

        $this->assertInstanceOf('Contao\Image\PictureConfigurationItem', $resizeConfig);
        $this->assertInstanceOf('Contao\Image\PictureConfigurationItemInterface', $resizeConfig);
    }

    /**
     * Tests the setResizeConfig() method.
     */
    public function testSetResizeConfig()
    {
        $config = new PictureConfigurationItem();
        $resizeConfig = $this->getMock('Contao\Image\ResizeConfigurationInterface');

        $this->assertInstanceOf('Contao\Image\ResizeConfigurationInterface', $config->getResizeConfig());
        $this->assertSame($config, $config->setResizeConfig($resizeConfig));
        $this->assertSame($resizeConfig, $config->getResizeConfig());
    }

    /**
     * Tests the setSizes() method.
     */
    public function testSetSizes()
    {
        $config = new PictureConfigurationItem();

        $this->assertEquals('', $config->getSizes());
        $this->assertSame($config, $config->setSizes('(min-width: 900px) 50vw, 100vw'));
        $this->assertEquals('(min-width: 900px) 50vw, 100vw', $config->getSizes());

        $config->setSizes(100);
        $this->assertInternalType('string', $config->getSizes());
    }

    /**
     * Tests the setDensities() method.
     */
    public function testSetDensities()
    {
        $config = new PictureConfigurationItem();

        $this->assertEquals('', $config->getDensities());
        $this->assertSame($config, $config->setDensities('1x, 2x, 200w, 400w'));
        $this->assertEquals('1x, 2x, 200w, 400w', $config->getDensities());

        $config->setDensities(100);
        $this->assertInternalType('string', $config->getDensities());
    }

    /**
     * Tests the setMedia() method.
     */
    public function testSetMedia()
    {
        $config = new PictureConfigurationItem();

        $this->assertEquals('', $config->getMedia());
        $this->assertSame($config, $config->setMedia('(max-width: 900px)'));
        $this->assertEquals('(max-width: 900px)', $config->getMedia());

        $config->setMedia(100);
        $this->assertInternalType('string', $config->getMedia());
    }
}
