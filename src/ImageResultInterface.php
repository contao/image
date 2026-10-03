<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface ImageResultInterface
{
    public function getIdentity(): TargetIdentity;

    public function getDimensions(): Dimensions;

    public function getFormat(): string;

    public function getUrl(): string;
}
