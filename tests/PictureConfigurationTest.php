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

        $this->expectException(InvalidArgumentException::class);

        $config->setSizeItems([$configItem, 'not a PictureConfigurationItem']);
    }

    public function testSetFormats(): void
    {
        $config = new PictureConfiguration();

        $this->assertSame(
            [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]],
            $config->getFormats()
        );

        $formats = ['png' => ['webp', 'png'], 'jpeg' => ['heic']];
        $expected = $formats + [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]];

        $this->assertSame($config, $config->setFormats($formats));
        $this->assertSame($expected, $config->getFormats());

        $formats = ['png' => ['webp', 'png'], PictureConfiguration::FORMAT_DEFAULT => ['heic']];
        $expected = $formats;

        $this->assertSame($config, $config->setFormats($formats));
        $this->assertSame($expected, $config->getFormats());

        $this->assertSame($config, $config->setFormats([]));

        $this->assertSame(
            [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]],
            $config->getFormats()
        );
    }

    public function testSetFormatsThrowsForInvalidSourceFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"not-valid"');

        (new PictureConfiguration())->setFormats(['not-valid' => ['png']]);
    }

    public function testSetFormatsThrowsForInvalidTargetFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"not-valid"');

        (new PictureConfiguration())->setFormats(['png' => ['not-valid']]);
    }
}
