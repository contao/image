<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\Resize\ResizeConfiguration;

/**
 * Tests the ResizeConfiguration class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $resizeConfig = new ResizeConfiguration();

        $this->assertInstanceOf('Contao\Image\Resize\ResizeConfiguration', $resizeConfig);
        $this->assertInstanceOf('Contao\Image\Resize\ResizeConfigurationInterface', $resizeConfig);
    }

    /**
     * Tests the isEmpty() method.
     */
    public function testIsEmpty()
    {
        $config = new ResizeConfiguration();

        $this->assertTrue($config->isEmpty());

        $config->setMode(ResizeConfiguration::MODE_CROP);

        $this->assertTrue($config->isEmpty());

        $config->setMode(ResizeConfiguration::MODE_PROPORTIONAL);

        $this->assertTrue($config->isEmpty());

        $config->setMode(ResizeConfiguration::MODE_BOX);

        $this->assertTrue($config->isEmpty());

        $config->setWidth(100);

        $this->assertFalse($config->isEmpty());

        $config->setWidth(0)->setHeight(100);

        $this->assertFalse($config->isEmpty());

        $config->setHeight(0)->setZoomLevel(100);

        $this->assertFalse($config->isEmpty());

        $config->setWidth(100)->setHeight(100)->setZoomLevel(100);

        $this->assertFalse($config->isEmpty());

        $config->setWidth(0)->setHeight(0)->setZoomLevel(0);

        $this->assertTrue($config->isEmpty());
    }

    /**
     * Tests the setWidth() method.
     */
    public function testSetWidth()
    {
        $config = new ResizeConfiguration();

        $this->assertEquals(0, $config->getWidth());
        $this->assertSame($config, $config->setWidth(100.0));
        $this->assertEquals(100, $config->getWidth());
        $this->assertInternalType('int', $config->getWidth());

        $this->setExpectedException('InvalidArgumentException');

        $config->setWidth(-1);
    }

    /**
     * Tests the setHeight() method.
     */
    public function testSetHeight()
    {
        $config = new ResizeConfiguration();

        $this->assertEquals(0, $config->getHeight());
        $this->assertSame($config, $config->setHeight(100.0));
        $this->assertEquals(100, $config->getHeight());
        $this->assertInternalType('int', $config->getHeight());

        $this->setExpectedException('InvalidArgumentException');

        $config->setHeight(-1);
    }

    /**
     * Tests the setMode() method.
     */
    public function testSetMode()
    {
        $config = new ResizeConfiguration();

        $this->assertEquals(ResizeConfiguration::MODE_CROP, $config->getMode());
        $this->assertSame($config, $config->setMode(ResizeConfiguration::MODE_BOX));
        $this->assertEquals(ResizeConfiguration::MODE_BOX, $config->getMode());

        $this->setExpectedException('InvalidArgumentException');

        $config->setMode('invalid');
    }

    /**
     * Tests the setZoomLevel() method.
     */
    public function testSetZoomLevel()
    {
        $config = new ResizeConfiguration();

        $this->assertEquals(0, $config->getZoomLevel());
        $this->assertSame($config, $config->setZoomLevel(100.0));
        $this->assertEquals(100, $config->getZoomLevel());
        $this->assertInternalType('int', $config->getZoomLevel());

        $this->setExpectedException('InvalidArgumentException');

        $config->setZoomLevel(-1);
    }

    /**
     * Tests the setZoomLevel() method.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSetZoomLevelTooHigh()
    {
        $config = new ResizeConfiguration();

        $this->setExpectedException('InvalidArgumentException');

        $config->setZoomLevel(101);
    }
}
