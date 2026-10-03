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

use Contao\Image\Exception\InvalidArgumentException;

final class SourceReference
{
    public function __construct(private string $storage, private string $key)
    {
        if ('' === $storage || '' === $key) {
            throw new InvalidArgumentException('Source storage and key must not be empty');
        }
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getHash(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'storage' => $this->storage];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['storage'], $data['key']);
    }
}
