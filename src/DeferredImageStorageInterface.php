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

interface DeferredImageStorageInterface
{
    public function set(string $path, array $value): void;

    public function get(string $path): array;

    public function getLocked(string $path, bool $blocking = true): ?array;

    public function releaseLock(string $path): void;

    public function delete(string $path): void;

    public function has(string $path): bool;

    /**
     * @return iterable<string>
     */
    public function listPaths(): iterable;
}
