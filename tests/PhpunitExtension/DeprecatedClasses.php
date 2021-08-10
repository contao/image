<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\PhpunitExtension;

use Contao\TestCase\DeprecatedClassesPhpunitExtension;
use Imagine\Image\Metadata\MetadataBag;

// PHPUnit 7.5 compatibility
if (!class_exists(DeprecatedClassesPhpunitExtension::class)) {
    eval('namespace Contao\Image\Tests\PhpunitExtension; class DeprecatedClasses implements \PHPUnit\Runner\Hook{}');

    return;
}

final class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        $deprecations = [];

        if (\PHP_VERSION_ID >= 80100) {
            $deprecations[MetadataBag::class] = [
                '%s::offsetExists%s#[ReturnTypeWillChange]%s',
                '%s::offsetGet%s#[ReturnTypeWillChange]%s',
                '%s::offsetSet%s#[ReturnTypeWillChange]%s',
                '%s::offsetUnset%s#[ReturnTypeWillChange]%s',
                '%s::getIterator%s#[ReturnTypeWillChange]%s',
                '%s::count%s#[ReturnTypeWillChange]%s',
            ];
        }

        return $deprecations;
    }
}
