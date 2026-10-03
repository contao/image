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

interface SourceInformationStorageInterface
{
    /**
     * @phpstan-impure
     */
    public function get(SourceVersion $source): ?ImageInformation;

    public function put(SourceVersion $source, ImageInformation $information): void;

    public function delete(SourceVersion $source): void;

    public function lock(SourceVersion $source): StorageLockInterface;
}
