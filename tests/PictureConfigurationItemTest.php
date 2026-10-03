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

use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use PHPUnit\Framework\TestCase;

class PictureConfigurationItemTest extends TestCase
{
    public function testWithResizeConfig(): void
    {
        $config = new PictureConfigurationItem();
        $resizeConfig = $this->createMock(ResizeConfiguration::class);

        $this->assertTrue($config->getResizeConfig()->isEmpty());
        $copy = $config->withResizeConfig($resizeConfig);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame($resizeConfig, $config->getResizeConfig());
    }

    public function testWithSizes(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getSizes());
        $copy = $config->withSizes('(min-width: 900px) 50vw, 100vw');
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame('(min-width: 900px) 50vw, 100vw', $config->getSizes());
    }

    public function testWithDensities(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getDensities());
        $copy = $config->withDensities('1x, 2x, 200w, 400w');
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame('1x, 2x, 200w, 400w', $config->getDensities());
    }

    public function testWithMedia(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getMedia());
        $copy = $config->withMedia('(max-width: 900px)');
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame('(max-width: 900px)', $config->getMedia());
    }
}
