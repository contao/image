<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Metadata;

class AvifContainer extends IsobmffContainer
{
    public function getMagicBytesOffset(): int
    {
        return 4;
    }

    public function getMagicBytes(): string
    {
        return 'ftypavif';
    }
}
