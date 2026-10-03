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
    public function testWithSize(): void
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItem::class);

        $copy = $config->withSize($configItem);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame($configItem, $config->getSize());
    }

    public function testWithSizeItems(): void
    {
        $config = new PictureConfiguration();
        $configItem = $this->createMock(PictureConfigurationItem::class);

        $this->assertSame([], $config->getSizeItems());
        $copy = $config->withSizeItems([$configItem]);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame([$configItem], $config->getSizeItems());

        $this->expectException(InvalidArgumentException::class);

        $config = $config->withSizeItems([$configItem, 'not a PictureConfigurationItem']);
    }

    public function testWithFormats(): void
    {
        $config = new PictureConfiguration();

        $this->assertSame(
            [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]],
            $config->getFormats(),
        );

        $formats = ['png' => ['webp', 'png'], 'jpeg' => ['heic']];
        $expected = $formats + [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]];

        $copy = $config->withFormats($formats);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame($expected, $config->getFormats());

        $formats = ['png' => ['webp', 'png'], PictureConfiguration::FORMAT_DEFAULT => ['heic']];
        $expected = $formats;

        $copy = $config->withFormats($formats);
        $this->assertNotSame($config, $copy);
        $config = $copy;
        $this->assertSame($expected, $config->getFormats());

        $copy = $config->withFormats([]);
        $this->assertNotSame($config, $copy);
        $config = $copy;

        $this->assertSame(
            [PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]],
            $config->getFormats(),
        );
    }

    public function testWithFormatsThrowsForInvalidSourceFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"not-valid"');

        (new PictureConfiguration())->withFormats(['not-valid' => ['png']]);
    }

    public function testWithFormatsThrowsForInvalidTargetFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"not-valid"');

        (new PictureConfiguration())->withFormats(['png' => ['not-valid']]);
    }
}
