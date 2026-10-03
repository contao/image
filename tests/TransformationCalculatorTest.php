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

use Contao\Image\Dimensions;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeConfiguration;
use Contao\Image\Size;
use Contao\Image\TransformationCalculator;
use PHPUnit\Framework\TestCase;

class TransformationCalculatorTest extends TestCase
{
    /**
     * @dataProvider calculations
     */
    public function testLegacyCalculationParity(array $case): void
    {
        [$configuration, $dimensions, $part, $expected] = $case;
        $config = (new ResizeConfiguration())->withWidth($configuration[0])->withHeight($configuration[1])->withMode($configuration[2])->withZoomLevel($configuration[3]);
        $original = new Dimensions(new Size($dimensions[0], $dimensions[1]), $dimensions[2] ? Dimensions::RELATIVE : Dimensions::ABSOLUTE);
        $result = (new TransformationCalculator())->calculate($config, $original, new ImportantPart(...$part));
        $this->assertSame([$expected['target_width'], $expected['target_height']], $result->getSize()->toArray());
        $this->assertSame([$expected['target_x'], $expected['target_y']], $result->getCropStart()->toArray());
        $this->assertSame([$expected['width'], $expected['height']], $result->getCropSize()->toArray());
    }

    public function calculations(): iterable
    {
        foreach (json_decode(file_get_contents(__DIR__.'/data/calculations.json'), true) as $name => $case) {
            yield $name => [$case];
        }
    }
}
