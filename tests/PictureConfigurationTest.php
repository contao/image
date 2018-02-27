<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItemInterface;
use PHPUnit\Framework\TestCase;

class PictureConfigurationTest extends TestCase
{
    public function testInstantiation()
    {
        $config = new PictureConfiguration();

        $this->assertInstanceOf('Contao\Image\PictureConfiguration', $config);
        $this->assertInstanceOf('Contao\Image\PictureConfigurationInterface', $config);
    }

    public function testSetSize()
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItemInterface::class);

        $this->assertInstanceOf('Contao\Image\PictureConfigurationItemInterface', $config->getSize());
        $this->assertSame($config, $config->setSize($configItem));
        $this->assertSame($configItem, $config->getSize());
    }

    public function testSetSizeItems()
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItemInterface::class);

        $this->assertSame([], $config->getSizeItems());
        $this->assertSame($config, $config->setSizeItems([$configItem]));
        $this->assertSame([$configItem], $config->getSizeItems());

        $this->expectException('InvalidArgumentException');

        $config->setSizeItems([$configItem, 'not a PictureConfigurationItem']);
    }
}
