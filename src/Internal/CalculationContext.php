<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Internal;

use Contao\Image\Dimensions;

final class CalculationContext
{
    public function __construct(public readonly Dimensions $dimensions, public readonly array $importantPart, public readonly float $zoom)
    {
    }
}
