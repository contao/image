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

use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use PHPUnit\Framework\TestCase;

class PictureConfigurationTest extends TestCase
{
    public function testSetSize(): void
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItem::class);

        $this->assertSame($config, $config->setSize($configItem));
        $this->assertSame($configItem, $config->getSize());
    }

    public function testSetSizeItems(): void
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItem::class);

        $this->assertSame([], $config->getSizeItems());
        $this->assertSame($config, $config->setSizeItems([$configItem]));
        $this->assertSame([$configItem], $config->getSizeItems());

        $this->expectException('InvalidArgumentException');

        $config->setSizeItems([$configItem, 'not a PictureConfigurationItem']);
    }
}
