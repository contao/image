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
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageRegExp('/'.$message.'/i');

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
        yield [0, 1, 1, 1, 'Y coordinate plus the height'];
        yield [0, 0.5, 1, 0.6, 'Y coordinate plus the height'];
    }
}
