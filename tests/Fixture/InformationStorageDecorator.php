<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Fixture;

use Contao\Image\ImageInformation;
use Contao\Image\SourceInformationStorageInterface;
use Contao\Image\SourceVersion;
use Contao\Image\StorageLockInterface;

final class InformationStorageDecorator implements SourceInformationStorageInterface
{
    public array $counts = [];

    public function __construct(private SourceInformationStorageInterface $inner)
    {
    }

    public function get(SourceVersion $source): ?ImageInformation
    {
        $this->counts['get'] = ($this->counts['get'] ?? 0) + 1;

        return $this->inner->get($source);
    }

    public function put(SourceVersion $source, ImageInformation $information): void
    {
        $this->counts['put'] = ($this->counts['put'] ?? 0) + 1;
        $this->inner->put($source, $information);
    }

    public function delete(SourceVersion $source): void
    {
        $this->inner->delete($source);
    }

    public function lock(SourceVersion $source): StorageLockInterface
    {
        $this->counts['lock'] = ($this->counts['lock'] ?? 0) + 1;

        return $this->inner->lock($source);
    }
}
