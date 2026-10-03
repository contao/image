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

use Contao\Image\LocalTargetContentStorageInterface;

final class ContentStorageDecorator implements LocalTargetContentStorageInterface
{
    public ?\Closure $beforeDelete = null;

    public array $counts = [];

    public function __construct(private LocalTargetContentStorageInterface $inner)
    {
    }

    public function getPath(string $key): string
    {
        return $this->inner->getPath($key);
    }

    public function getUrl(string $key): string
    {
        return $this->inner->getUrl($key);
    }

    public function openStream(string $key)
    {
        $this->counts['reads'] = ($this->counts['reads'] ?? 0) + 1;

        return $this->inner->openStream($key);
    }

    public function publish(string $key, $stream): void
    {
        $this->counts['writes'] = ($this->counts['writes'] ?? 0) + 1;
        $this->inner->publish($key, $stream);
    }

    public function delete(string $key): void
    {
        if (null !== $this->beforeDelete) {
            ($this->beforeDelete)();
        }
        $this->inner->delete($key);
    }
}
