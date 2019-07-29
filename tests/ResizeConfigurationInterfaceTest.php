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

use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use PHPUnit\Framework\TestCase;

class ResizeConfigurationInterfaceTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation Using the "ResizeConfigurationInterface" has been deprecated and will no longer work in contao/image 2.0. Use the "Contao\Image\ResizeConfiguration" class instead.
     */
    public function testConstants(): void
    {
        $this->assertSame(ResizeConfiguration::MODE_CROP, ResizeConfigurationInterface::MODE_CROP);
        $this->assertSame(ResizeConfiguration::MODE_BOX, ResizeConfigurationInterface::MODE_BOX);
        $this->assertSame(ResizeConfiguration::MODE_PROPORTIONAL, ResizeConfigurationInterface::MODE_PROPORTIONAL);
    }
}
