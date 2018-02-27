<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfigurationInterface;
use PHPUnit\Framework\TestCase;

class PictureConfigurationItemTest extends TestCase
{
    public function testInstantiation()
    {
        $resizeConfig = new PictureConfigurationItem();

        $this->assertInstanceOf('Contao\Image\PictureConfigurationItem', $resizeConfig);
        $this->assertInstanceOf('Contao\Image\PictureConfigurationItemInterface', $resizeConfig);
    }

    public function testSetResizeConfig()
    {
        $config = new PictureConfigurationItem();
        $resizeConfig = $this->createMock(ResizeConfigurationInterface::class);

        $this->assertInstanceOf('Contao\Image\ResizeConfigurationInterface', $config->getResizeConfig());
        $this->assertSame($config, $config->setResizeConfig($resizeConfig));
        $this->assertSame($resizeConfig, $config->getResizeConfig());
    }

    public function testSetSizes()
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getSizes());
        $this->assertSame($config, $config->setSizes('(min-width: 900px) 50vw, 100vw'));
        $this->assertSame('(min-width: 900px) 50vw, 100vw', $config->getSizes());

        $config->setSizes(100);
        $this->assertInternalType('string', $config->getSizes());
    }

    public function testSetDensities()
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getDensities());
        $this->assertSame($config, $config->setDensities('1x, 2x, 200w, 400w'));
        $this->assertSame('1x, 2x, 200w, 400w', $config->getDensities());

        $config->setDensities(100);
        $this->assertInternalType('string', $config->getDensities());
    }

    public function testSetMedia()
    {
        $config = new PictureConfigurationItem();

        $this->assertSame('', $config->getMedia());
        $this->assertSame($config, $config->setMedia('(max-width: 900px)'));
        $this->assertSame('(max-width: 900px)', $config->getMedia());

        $config->setMedia(100);
        $this->assertInternalType('string', $config->getMedia());
    }
}
