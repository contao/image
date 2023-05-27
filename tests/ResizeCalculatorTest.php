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
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Imagine\Image\Box;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ResizeCalculatorTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider getCalculateDataWithoutImportantPart
     */
    public function testCalculateWithoutImportantPart(array $arguments, array $expectedResult): void
    {
        $calculator = new ResizeCalculator();

        $expected = new ResizeCoordinates(
            new Box($expectedResult['target_width'], $expectedResult['target_height']),
            new Point($expectedResult['target_x'], $expectedResult['target_y']),
            new Box($expectedResult['width'], $expectedResult['height'])
        );

        $config = (new ResizeConfiguration())->setWidth((int) $arguments[0])->setHeight((int) $arguments[1]);
        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]), !empty($arguments[5]));
        $importantPart = null;

        if ($arguments[4] && 1 === substr_count($arguments[4], '_')) {
            $importantPart = ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1];
            $mode = explode('_', $arguments[4]);

            if ('left' === $mode[0]) {
                $importantPart['width'] = 0;
            } elseif ('right' === $mode[0]) {
                $importantPart['x'] = 1;
                $importantPart['width'] = 0;
            }

            if ('top' === $mode[1]) {
                $importantPart['height'] = 0;
            } elseif ('bottom' === $mode[1]) {
                $importantPart['y'] = 1;
                $importantPart['height'] = 0;
            }

            $arguments[4] = 'crop';

            $importantPart = new ImportantPart(
                $importantPart['x'],
                $importantPart['y'],
                $importantPart['width'],
                $importantPart['height']
            );
        }

        if (ResizeConfiguration::MODE_PROPORTIONAL === $arguments[4]) {
            $this->expectDeprecation('Using ResizeConfiguration::MODE_PROPORTIONAL has been deprecated%s');
        }

        if ($arguments[4]) {
            $config->setMode($arguments[4]);
        }

        $this->assertSameCoordinates($expected, $calculator->calculate($config, $dimensions, $importantPart));

        if (null !== $importantPart) {
            return;
        }

        $config->setZoomLevel(50);

        $this->assertSameCoordinates(
            $expected,
            $calculator->calculate($config, $dimensions),
            'Zoom 50 should return the same results if no important part is specified'
        );

        $config->setZoomLevel(100);

        $this->assertSameCoordinates(
            $expected,
            $calculator->calculate($config, $dimensions),
            'Zoom 100 should return the same results if no important part is specified'
        );

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]));

        if (empty($arguments[5])) {
            $this->assertSameCoordinates(
                $expected,
                $calculator->calculate($config, $dimensions),
                'Up scaling should have no effect'
            );
        } else {
            $this->assertNotSameCoordinates(
                $expected,
                $calculator->calculate($config, $dimensions),
                'Up scaling should have an effect'
            );
        }
    }

    public function getCalculateDataWithoutImportantPart(): \Generator
    {
        yield 'No dimensions' => [
            [null, null, 100, 100, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Same dimensions' => [
            [100, 100, 100, 100, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Scale down' => [
            [50, 50, 100, 100, null],
            [
                'width' => 50,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 50,
            ],
        ];

        yield 'Scale up' => [
            [100, 100, 50, 50, null, true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Do not scale up' => [
            [100, 100, 50, 50, null],
            [
                'width' => 50,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 50,
            ],
        ];

        yield 'Width only' => [
            [100, null, 50, 50, null, true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Height only' => [
            [null, 100, 50, 50, null, true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Crop landscape' => [
            [100, 50, 100, 100, null],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 25,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Crop portrait' => [
            [50, 100, 100, 100, null],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => 25,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Mode proportional landscape' => [
            [100, 10, 100, 50, 'proportional'],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ],
        ];

        yield 'Mode proportional portrait' => [
            [10, 100, 50, 100, 'proportional'],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ],
        ];

        yield 'Mode proportional square' => [
            [100, 50, 100, 100, 'proportional'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Mode box landscape 1' => [
            [100, 100, 100, 50, 'box'],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ],
        ];

        yield 'Mode box landscape 2' => [
            [100, 10, 100, 50, 'box'],
            [
                'width' => 20,
                'height' => 10,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 20,
                'target_height' => 10,
            ],
        ];

        yield 'Mode box portrait 1' => [
            [100, 100, 50, 100, 'box'],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ],
        ];

        yield 'Mode box portrait 2' => [
            [10, 100, 50, 100, 'box'],
            [
                'width' => 10,
                'height' => 20,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 10,
                'target_height' => 20,
            ],
        ];

        yield 'Mode left_top landscape' => [
            [100, 100, 100, 50, 'left_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_top portrait' => [
            [100, 100, 50, 100, 'left_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_top landscape' => [
            [100, 100, 100, 50, 'center_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_top portrait' => [
            [100, 100, 50, 100, 'center_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_top landscape' => [
            [100, 100, 100, 50, 'right_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_top portrait' => [
            [100, 100, 50, 100, 'right_top', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode left_center landscape' => [
            [100, 100, 100, 50, 'left_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_center portrait' => [
            [100, 100, 50, 100, 'left_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_center landscape' => [
            [100, 100, 100, 50, 'center_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_center portrait' => [
            [100, 100, 50, 100, 'center_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_center landscape' => [
            [100, 100, 100, 50, 'right_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_center portrait' => [
            [100, 100, 50, 100, 'right_center', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode left_bottom landscape' => [
            [100, 100, 100, 50, 'left_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_bottom portrait' => [
            [100, 100, 50, 100, 'left_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_bottom landscape' => [
            [100, 100, 100, 50, 'center_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_bottom portrait' => [
            [100, 100, 50, 100, 'center_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_bottom landscape' => [
            [100, 100, 100, 50, 'right_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_bottom portrait' => [
            [100, 100, 50, 100, 'right_bottom', true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Do not round down to crop height zero' => [
            [1000, 1, 100, 100, 'crop'],
            [
                'width' => 100,
                'height' => 1,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Do not round down to crop width zero' => [
            [1, 1000, 100, 100, 'crop'],
            [
                'width' => 1,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Do not round down to resize height zero' => [
            [100, 100, 1000, 1, 'box'],
            [
                'width' => 100,
                'height' => 1,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 1,
            ],
        ];

        yield 'Do not round down to resize width zero' => [
            [100, 100, 1, 1000, 'box'],
            [
                'width' => 1,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 1,
                'target_height' => 100,
            ],
        ];
    }

    /**
     * @dataProvider getCalculateDataWithImportantPart
     */
    public function testCalculateWithImportantPart(array $arguments, array $expectedResult): void
    {
        $calculator = new ResizeCalculator();

        $expected = new ResizeCoordinates(
            new Box($expectedResult['target_width'], $expectedResult['target_height']),
            new Point($expectedResult['target_x'], $expectedResult['target_y']),
            new Box($expectedResult['width'], $expectedResult['height'])
        );

        $config = new ResizeConfiguration();

        if (null !== $arguments[0]) {
            $config->setWidth($arguments[0]);
        }

        if (null !== $arguments[1]) {
            $config->setHeight($arguments[1]);
        }

        if (null !== $arguments[5]) {
            $config->setZoomLevel($arguments[5]);
        }

        if (null !== $arguments[4]) {
            $config->setMode($arguments[4]);
        }

        $importantPart = new ImportantPart(
            $arguments[6]['x'] / $arguments[2],
            $arguments[6]['y'] / $arguments[3],
            $arguments[6]['width'] / $arguments[2],
            $arguments[6]['height'] / $arguments[3]
        );

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]), !empty($arguments[7]));

        $this->assertSameCoordinates($expected, $calculator->calculate($config, $dimensions, $importantPart));

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]));

        if (empty($arguments[7])) {
            $this->assertSameCoordinates(
                $expected,
                $calculator->calculate($config, $dimensions, $importantPart),
                'Up scaling should have no effect'
            );
        } else {
            $this->assertNotSameCoordinates(
                $expected,
                $calculator->calculate($config, $dimensions, $importantPart),
                'Up scaling should have an effect'
            );
        }
    }

    public function getCalculateDataWithImportantPart(): \Generator
    {
        yield 'No dimensions zoom 0' => [
            [null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'No dimensions zoom 50' => [
            [null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 80,
                'height' => 80,
                'target_x' => 10,
                'target_y' => 10,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'No dimensions zoom 100' => [
            [null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 60,
                'height' => 60,
                'target_x' => 20,
                'target_y' => 20,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Width only zoom 0' => [
            [100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Width only zoom 50' => [
            [100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60], true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 13,
                'target_y' => 13,
                'target_width' => 125,
                'target_height' => 125,
            ],
        ];

        yield 'Width only zoom 100' => [
            [100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60], true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 33,
                'target_y' => 33,
                'target_width' => 167,
                'target_height' => 167,
            ],
        ];

        yield 'Same dimensions zoom 0' => [
            [100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Same dimensions zoom 50' => [
            [100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50], true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 17,
                'target_y' => 17,
                'target_width' => 133,
                'target_height' => 133,
            ],
        ];

        yield 'Same dimensions zoom 100' => [
            [100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50], true],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 50,
                'target_width' => 200,
                'target_height' => 200,
            ],
        ];

        yield 'Landscape to portrait zoom 0' => [
            [100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => 233,
                'target_y' => 0,
                'target_width' => 400,
                'target_height' => 200,
            ],
        ];

        yield 'Landscape to portrait zoom 50' => [
            [100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => 367,
                'target_y' => 43,
                'target_width' => 571,
                'target_height' => 286,
            ],
        ];

        yield 'Landscape to portrait zoom 100' => [
            [100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => 700,
                'target_y' => 150,
                'target_width' => 1000,
                'target_height' => 500,
            ],
        ];

        yield 'Do not round down to crop height zero' => [
            [1000, 1, 100, 100, 'crop', 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 100,
                'height' => 1,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Do not round down to crop width zero' => [
            [1, 1000, 100, 100, 'crop', 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 1,
                'height' => 100,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Do not round down to resize height zero' => [
            [100, 100, 1000, 1, 'box', 100, ['x' => 250, 'y' => 0, 'width' => 500, 'height' => 1]],
            [
                'width' => 100,
                'height' => 1,
                'target_x' => 50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 1,
            ],
        ];

        yield 'Do not round down to resize width zero' => [
            [100, 100, 1, 1000, 'box', 100, ['x' => 1, 'y' => 250, 'width' => 0, 'height' => 500]],
            [
                'width' => 1,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 50,
                'target_width' => 1,
                'target_height' => 200,
            ],
        ];
    }

    /**
     * Tests the calculate() method with an invalid resize mode.
     */
    public function testCalculateWithInvalidResizeMode(): void
    {
        $calculator = new ResizeCalculator();

        $config = $this->createMock(ResizeConfiguration::class);
        $config
            ->method('getWidth')
            ->willReturn(200)
        ;

        $config
            ->method('getHeight')
            ->willReturn(200)
        ;

        $config
            ->method('getMode')
            ->willReturn('invalid')
        ;

        $dimensions = new ImageDimensions(new Box(100, 100));

        $this->expectException(InvalidArgumentException::class);

        $calculator->calculate($config, $dimensions);
    }

    private function assertSameCoordinates(ResizeCoordinates $expected, ResizeCoordinates $actual, string $message = ''): void
    {
        $this->assertTrue($expected->isEqualTo($actual), $message);
        $this->assertTrue($actual->isEqualTo($expected), $message);
    }

    private function assertNotSameCoordinates(ResizeCoordinates $expected, ResizeCoordinates $actual, string $message = ''): void
    {
        $this->assertFalse($expected->isEqualTo($actual), $message);
        $this->assertFalse($actual->isEqualTo($expected), $message);
    }
}
