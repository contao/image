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
    public function testSetResizeConfig(): void
    {
        $config = new PictureConfigurationItem();
        $resizeConfig = $this->createMock(ResizeConfiguration::class);

        $this->assertTrue($config->getResizeConfig()->isEmpty());
        $this->assertSame($config, $config->setResizeConfig($resizeConfig));
        $this->assertSame($resizeConfig, $config->getResizeConfig());
    }

    public function testSetSizes(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getSizes());
        $this->assertSame($config, $config->setSizes('(min-width: 900px) 50vw, 100vw'));
        $this->assertSame('(min-width: 900px) 50vw, 100vw', $config->getSizes());
    }

    public function testSetDensities(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getDensities());
        $this->assertSame($config, $config->setDensities('1x, 2x, 200w, 400w'));
        $this->assertSame('1x, 2x, 200w, 400w', $config->getDensities());
    }

    public function testSetMedia(): void
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getMedia());
        $this->assertSame($config, $config->setMedia('(max-width: 900px)'));
        $this->assertSame('(max-width: 900px)', $config->getMedia());
    }
}
