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

class JxlContainer extends IsobmffContainer
{
    public function getMagicBytes(): string
    {
        return "\x00\x00\x00\x0CJXL \x0D\x0A\x87\x0A";
    }
}
