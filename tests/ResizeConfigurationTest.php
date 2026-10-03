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

        $config = $config->withMode(ResizeConfiguration::MODE_CROP);

        $this->assertTrue($config->isEmpty());

        $config = $config->withMode(ResizeConfiguration::MODE_PROPORTIONAL);

        $this->assertTrue($config->isEmpty());

        $config = $config->withMode(ResizeConfiguration::MODE_BOX);

        $this->assertTrue($config->isEmpty());

        $config = $config->withWidth(100);

        $this->assertFalse($config->isEmpty());

        $config = $config->withWidth(0)->withHeight(100);

        $this->assertFalse($config->isEmpty());

        $config = $config->withHeight(0)->withZoomLevel(100);

        $this->assertFalse($config->isEmpty());

        $config = $config->withWidth(100)->withHeight(100)->withZoomLevel(100);

        $this->assertFalse($config->isEmpty());

        $config = $config->withWidth(0)->withHeight(0)->withZoomLevel(0);

        $this->assertTrue($config->isEmpty());
    }

    public function testWithWidth(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getWidth());
        $copy = $config->withWidth(100);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame(100, $config->getWidth());

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withWidth(-1);
    }

    public function testWithHeight(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getHeight());
        $copy = $config->withHeight(100);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame(100, $config->getHeight());

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withHeight(-1);
    }

    public function testWithMode(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(ResizeConfiguration::MODE_CROP, $config->getMode());
        $copy = $config->withMode(ResizeConfiguration::MODE_BOX);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withMode('invalid');
    }

    public function testWithZoomLevel(): void
    {
        $config = new ResizeConfiguration();

        $this->assertSame(0, $config->getZoomLevel());
        $copy = $config->withZoomLevel(100);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame(100, $config->getZoomLevel());

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withZoomLevel(-1);
    }

    public function testWithZoomLevelTooHigh(): void
    {
        $config = new ResizeConfiguration();

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withZoomLevel(101);
    }
}
