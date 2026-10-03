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

interface ResponsivePictureInterface
{
    public function getImg(): array;

    public function getSources(): array;

    public function getRawImg(): array;

    public function getRawSources(): array;
}
