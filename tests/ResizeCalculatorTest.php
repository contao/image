<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinates;
use Imagine\Image\Box;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;

class ResizeCalculatorTest extends TestCase
{
    public function testInstantiation()
    {
        $calculator = new ResizeCalculator();

        $this->assertInstanceOf('Contao\Image\ResizeCalculator', $calculator);
        $this->assertInstanceOf('Contao\Image\ResizeCalculatorInterface', $calculator);
    }

    /**
     * @param array $arguments
     * @param array $expectedResult
     *
     * @dataProvider getCalculateDataWithoutImportantPart
     */
    public function testCalculateWithoutImportantPart(array $arguments, array $expectedResult)
    {
        $calculator = new ResizeCalculator();

        $expected = new ResizeCoordinates(
            new Box($expectedResult['target_width'], $expectedResult['target_height']),
            new Point($expectedResult['target_x'], $expectedResult['target_y']),
            new Box($expectedResult['width'], $expectedResult['height'])
        );

        $config = (new ResizeConfiguration())->setWidth($arguments[0])->setHeight($arguments[1]);
        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]), !empty($arguments[5]));
        $importantPart = null;

        if ($arguments[4] && 1 === substr_count($arguments[4], '_')) {
            $importantPart = ['x' => 0, 'y' => 0, 'width' => $arguments[2], 'height' => $arguments[3]];
            $mode = explode('_', $arguments[4]);

            if ('left' === $mode[0]) {
                $importantPart['width'] = 1;
            } elseif ('right' === $mode[0]) {
                $importantPart['x'] = $arguments[2] - 1;
                $importantPart['width'] = 1;
            }

            if ('top' === $mode[1]) {
                $importantPart['height'] = 1;
            } elseif ('bottom' === $mode[1]) {
                $importantPart['y'] = $arguments[3] - 1;
                $importantPart['height'] = 1;
            }

            $arguments[4] = 'crop';

            $importantPart = new ImportantPart(
                new Point($importantPart['x'], $importantPart['y']),
                new Box($importantPart['width'], $importantPart['height'])
            );
        }

        if ($arguments[4]) {
            $config->setMode($arguments[4]);
        }

        $this->assertEquals($expected, $calculator->calculate($config, $dimensions, $importantPart));

        if (null !== $importantPart) {
            return;
        }

        $config->setZoomLevel(50);

        $this->assertEquals(
            $expected,
            $calculator->calculate($config, $dimensions),
            'Zoom 50 should return the same results if no important part is specified'
        );

        $config->setZoomLevel(100);

        $this->assertEquals(
            $expected,
            $calculator->calculate($config, $dimensions),
            'Zoom 100 should return the same results if no important part is specified'
        );

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]));

        if (empty($arguments[5])) {
            $this->assertEquals(
                $expected,
                $calculator->calculate($config, $dimensions),
                'Up scaling should have no effect'
            );
        } else {
            $this->assertNotEquals(
                $expected,
                $calculator->calculate($config, $dimensions),
                'Up scaling should have an effect'
            );
        }
    }

    /**
     * @return array
     */
    public function getCalculateDataWithoutImportantPart()
    {
        return [
            'No dimensions' => [
                [null, null, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Same dimensions' => [
                [100, 100, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Scale down' => [
                [50, 50, 100, 100, null],
                [
                    'width' => 50,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 50,
                ],
            ],
            'Scale up' => [
                [100, 100, 50, 50, null, true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Do not scale up' => [
                [100, 100, 50, 50, null],
                [
                    'width' => 50,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 50,
                ],
            ],
            'Width only' => [
                [100, null, 50, 50, null, true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Height only' => [
                [null, 100, 50, 50, null, true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Crop landscape' => [
                [100, 50, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 25,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Crop portrait' => [
                [50, 100, 100, 100, null],
                [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => 25,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Mode proportional landscape' => [
                [100, 10, 100, 50, 'proportional'],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 50,
                ],
            ],
            'Mode proportional portrait' => [
                [10, 100, 50, 100, 'proportional'],
                [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 100,
                ],
            ],
            'Mode proportional square' => [
                [100, 50, 100, 100, 'proportional'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Mode box landscape 1' => [
                [100, 100, 100, 50, 'box'],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 50,
                ],
            ],
            'Mode box landscape 2' => [
                [100, 10, 100, 50, 'box'],
                [
                    'width' => 20,
                    'height' => 10,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 20,
                    'target_height' => 10,
                ],
            ],
            'Mode box portrait 1' => [
                [100, 100, 50, 100, 'box'],
                    [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 100,
                ],
            ],
            'Mode box portrait 2' => [
                [10, 100, 50, 100, 'box'],
                [
                    'width' => 10,
                    'height' => 20,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 10,
                    'target_height' => 20,
                ],
            ],
            'Mode left_top landscape' => [
                [100, 100, 100, 50, 'left_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_top portrait' => [
                [100, 100, 50, 100, 'left_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_top landscape' => [
                [100, 100, 100, 50, 'center_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_top portrait' => [
                [100, 100, 50, 100, 'center_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_top landscape' => [
                [100, 100, 100, 50, 'right_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_top portrait' => [
                [100, 100, 50, 100, 'right_top', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode left_center landscape' => [
                [100, 100, 100, 50, 'left_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_center portrait' => [
                [100, 100, 50, 100, 'left_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_center landscape' => [
                [100, 100, 100, 50, 'center_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_center portrait' => [
                [100, 100, 50, 100, 'center_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_center landscape' => [
                [100, 100, 100, 50, 'right_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_center portrait' => [
                [100, 100, 50, 100, 'right_center', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode left_bottom landscape' => [
                [100, 100, 100, 50, 'left_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_bottom portrait' => [
                [100, 100, 50, 100, 'left_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_bottom landscape' => [
                [100, 100, 100, 50, 'center_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_bottom portrait' => [
                [100, 100, 50, 100, 'center_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_bottom landscape' => [
                [100, 100, 100, 50, 'right_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_bottom portrait' => [
                [100, 100, 50, 100, 'right_bottom', true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Float values' => [
                [100.4, 100.4, 50, 50, null, true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
        ];
    }

    /**
     * @param array $arguments
     * @param array $expectedResult
     *
     * @dataProvider getCalculateDataWithImportantPart
     */
    public function testCalculateWithImportantPart(array $arguments, array $expectedResult)
    {
        $calculator = new ResizeCalculator();

        $expected = new ResizeCoordinates(
            new Box($expectedResult['target_width'], $expectedResult['target_height']),
            new Point($expectedResult['target_x'], $expectedResult['target_y']),
            new Box($expectedResult['width'], $expectedResult['height'])
        );

        $config = (new ResizeConfiguration())
            ->setWidth($arguments[0])
            ->setHeight($arguments[1])
            ->setZoomLevel($arguments[5])
        ;

        if ($arguments[4]) {
            $config->setMode($arguments[4]);
        }

        $importantPart = new ImportantPart(
            new Point($arguments[6]['x'], $arguments[6]['y']),
            new Box($arguments[6]['width'], $arguments[6]['height'])
        );

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]), !empty($arguments[7]));

        $this->assertEquals($expected, $calculator->calculate($config, $dimensions, $importantPart));

        $dimensions = new ImageDimensions(new Box($arguments[2], $arguments[3]));

        if (empty($arguments[7])) {
            $this->assertEquals(
                $expected,
                $calculator->calculate($config, $dimensions, $importantPart),
                'Up scaling should have no effect'
            );
        } else {
            $this->assertNotEquals(
                $expected,
                $calculator->calculate($config, $dimensions, $importantPart),
                'Up scaling should have an effect'
            );
        }
    }

    /**
     * @return array The data
     */
    public function getCalculateDataWithImportantPart()
    {
        return [
            'No dimensions zoom 0' => [
                [null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'No dimensions zoom 50' => [
                [null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 80,
                    'height' => 80,
                    'target_x' => 10,
                    'target_y' => 10,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'No dimensions zoom 100' => [
                [null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 60,
                    'height' => 60,
                    'target_x' => 20,
                    'target_y' => 20,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Width only zoom 0' => [
                [100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Width only zoom 50' => [
                [100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60], true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 13,
                    'target_y' => 13,
                    'target_width' => 125,
                    'target_height' => 125,
                ],
            ],
            'Width only zoom 100' => [
                [100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60], true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 33,
                    'target_y' => 33,
                    'target_width' => 167,
                    'target_height' => 167,
                ],
            ],
            'Same dimensions zoom 0' => [
                [100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Same dimensions zoom 50' => [
                [100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50], true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 17,
                    'target_y' => 17,
                    'target_width' => 133,
                    'target_height' => 133,
                ],
            ],
            'Same dimensions zoom 100' => [
                [100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50], true],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 50,
                    'target_y' => 50,
                    'target_width' => 200,
                    'target_height' => 200,
                ],
            ],
            'Landscape to portrait zoom 0' => [
                [100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => 233,
                    'target_y' => 0,
                    'target_width' => 400,
                    'target_height' => 200,
                ],
            ],
            'Landscape to portrait zoom 50' => [
                [100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => 367,
                    'target_y' => 43,
                    'target_width' => 571,
                    'target_height' => 286,
                ],
            ],
            'Landscape to portrait zoom 100' => [
                [100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20], true],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => 700,
                    'target_y' => 150,
                    'target_width' => 1000,
                    'target_height' => 500,
                ],
            ],
        ];
    }

    /**
     * Tests the calculate() method with an invalid resize mode.
     */
    public function testCalculateWithInvalidResizeMode()
    {
        $calculator = new ResizeCalculator();

        $config = $this->createMock(ResizeConfigurationInterface::class);
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

        $this->expectException('InvalidArgumentException');

        $calculator->calculate($config, $dimensions);
    }
}
