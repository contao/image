<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\PictureConfiguration;

/**
 * Tests the PictureConfiguration class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $config = new PictureConfiguration();

        $this->assertInstanceOf('Contao\Image\PictureConfiguration', $config);
        $this->assertInstanceOf('Contao\Image\PictureConfigurationInterface', $config);
    }

    /**
     * Tests the setSize() method.
     */
    public function testSetSize()
    {
        $config = new PictureConfiguration();
        $configItem = $this->getMock('Contao\Image\PictureConfigurationItemInterface');

        $this->assertInstanceOf('Contao\Image\PictureConfigurationItemInterface', $config->getSize());
        $this->assertSame($config, $config->setSize($configItem));
        $this->assertSame($configItem, $config->getSize());
    }

    /**
     * Tests the setSizeItems() method.
     */
    public function testSetSizeItems()
    {
        $config = new PictureConfiguration();
        $configItem = $this->getMock('Contao\Image\PictureConfigurationItemInterface');

        $this->assertEquals([], $config->getSizeItems());
        $this->assertSame($config, $config->setSizeItems([$configItem]));
        $this->assertEquals([$configItem], $config->getSizeItems());

        $this->setExpectedException('InvalidArgumentException');

        $config->setSizeItems([$configItem, 'not a PictureConfigurationItem']);
    }
}
