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

use Contao\Image\Exception\CoordinatesOutOfBoundsException;
use Contao\Image\ImportantPart;
use PHPUnit\Framework\TestCase;

class ImportantPartTest extends TestCase
{
    public function testGetters(): void
    {
        $importantPart = new ImportantPart();

        $this->assertSame(0.0, $importantPart->getX());
        $this->assertSame(0.0, $importantPart->getY());
        $this->assertSame(1.0, $importantPart->getWidth());
        $this->assertSame(1.0, $importantPart->getHeight());

        $importantPart = new ImportantPart(0.1, 0.2, 0.3, 0.4);

        $this->assertSame(0.1, $importantPart->getX());
        $this->assertSame(0.2, $importantPart->getY());
        $this->assertSame(0.3, $importantPart->getWidth());
        $this->assertSame(0.4, $importantPart->getHeight());
    }

    /**
     * @dataProvider getInvalidValues
     */
    public function testInvalidValuesThrowsException(float $x, float $y, float $width, float $height, string $message): void
    {
        $this->expectException(CoordinatesOutOfBoundsException::class);

        if (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/'.$message.'/i');
        } else {
            $this->expectExceptionMessageMatches('/'.$message.'/i');
        }

        new ImportantPart($x, $y, $width, $height);
    }

    public function getInvalidValues(): \Generator
    {
        yield [2, 0, 1, 1, 'float between 0 and 1'];
        yield [0, 2, 1, 1, 'float between 0 and 1'];
        yield [0, 0, 2, 1, 'float between 0 and 1'];
        yield [0, 0, 1, 2, 'float between 0 and 1'];
        yield [-1, 0, 1, 1, 'float between 0 and 1'];
        yield [0, -1, 1, 1, 'float between 0 and 1'];
        yield [0, 0, -1, 1, 'float between 0 and 1'];
        yield [0, 0, 1, -1, 'float between 0 and 1'];
        yield [1, 0, 1, 1, 'X coordinate plus the width'];
        yield [0.5, 0, 0.6, 1, 'X coordinate plus the width'];
        yield [1, 0, 5E-5, 1, 'X coordinate plus the width'];
        yield [0, 1, 1, 1, 'Y coordinate plus the height'];
        yield [0, 0.5, 1, 0.6, 'Y coordinate plus the height'];
        yield [0, 1, 1, 5E-5, 'Y coordinate plus the height'];
    }

    /**
     * @dataProvider getValuesWithRoundingErrors
     */
    public function testValuesWithRoundingErrorsDoNotThrow(float $x, float $y, float $width, float $height): void
    {
        $importantPart = new ImportantPart($x, $y, $width, $height);

        $this->assertLessThanOrEqual(1.0, $importantPart->getX() + $importantPart->getWidth());
        $this->assertLessThanOrEqual(1.0, $importantPart->getY() + $importantPart->getHeight());
    }

    public function getValuesWithRoundingErrors(): \Generator
    {
        yield [3E-16, 0, 1, 1];
        yield [0, 3E-16, 1, 1];
        yield [0.5 + 3E-16, 0.5, 0.5, 0.5];
        yield [0.5, 0.5 + 3E-16, 0.5, 0.5];
        yield [0.5, 0.5, 0.5 + 3E-16, 0.5];
        yield [0.5, 0.5, 0.5, 0.5 + 3E-16];
        yield [5E-6, 0, 1, 1];
        yield [0, 5E-6, 1, 1];
        yield [0.5 + 5E-6, 0.5, 0.5, 0.5];
        yield [0.5, 0.5 + 5E-6, 0.5, 0.5];
        yield [0.5, 0.5, 0.5 + 5E-6, 0.5];
        yield [0.5, 0.5, 0.5, 0.5 + 5E-6];
    }
}
