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

use Contao\Image\Internal\Json;

final class TargetDescription
{
    public function __construct(private string $backend, private string $key, private array $data = [])
    {
        $this->data = Json::normalize($data);
    }

    public function getBackend(): string
    {
        return $this->backend;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return ['backend' => $this->backend, 'key' => $this->key, 'data' => $this->data];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['backend'], $data['key'], $data['data']);
    }
}
