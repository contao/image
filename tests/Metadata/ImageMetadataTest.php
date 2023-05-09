<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Metadata;

use Contao\Image\Metadata\ImageMetadata;
use PHPUnit\Framework\TestCase;

class ImageMetadataTest extends TestCase
{
    public function testGetFormat(): void
    {
        $byFormat = [
            'a' => [
                'foo' => 'bar',
            ],
            'b' => [
                'bar' => 'baz',
            ],
        ];

        $metadata = new ImageMetadata($byFormat);

        $this->assertSame(['foo' => 'bar'], $metadata->getFormat('a'));
        $this->assertSame(['bar' => 'baz'], $metadata->getFormat('b'));
        $this->assertSame([], $metadata->getFormat('c'));
        $this->assertSame($byFormat, $metadata->getAll());
    }
}
