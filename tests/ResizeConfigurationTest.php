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

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\ResizeConfiguration;
use PHPUnit\Framework\TestCase;

class ResizeConfigurationTest extends TestCase
{
    public function testIsEmpty(): void
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

    public function testSetWidth(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getWidth());
        $this->assertSame($config, $config->setWidth(100));
        $this->assertSame(100, $config->getWidth());

        $this->expectException(InvalidArgumentException::class);

        $config->setWidth(-1);
    }

    public function testSetHeight(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getHeight());
        $this->assertSame($config, $config->setHeight(100));
        $this->assertSame(100, $config->getHeight());

        $this->expectException(InvalidArgumentException::class);

        $config->setHeight(-1);
    }

    public function testSetMode(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(ResizeConfiguration::MODE_CROP, $config->getMode());
        $this->assertSame($config, $config->setMode(ResizeConfiguration::MODE_BOX));
        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());

        $this->expectException(InvalidArgumentException::class);

        $config->setMode('invalid');
    }

    public function testSetZoomLevel(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getZoomLevel());
        $this->assertSame($config, $config->setZoomLevel(100));
        $this->assertSame(100, $config->getZoomLevel());

        $this->expectException(InvalidArgumentException::class);

        $config->setZoomLevel(-1);
    }

    public function testSetZoomLevelTooHigh(): void
    {
        $config = new ResizeConfiguration();

        $this->expectException(InvalidArgumentException::class);

        $config->setZoomLevel(101);
    }
}
