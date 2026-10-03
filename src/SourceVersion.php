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
use Contao\Image\Exception\SourceVersionMismatchException;

final class SourceVersion
{
    public function __construct(
        private SourceReference $reference,
        private string $version,
    ) {
        if ('' === $version) {
            throw new InvalidArgumentException('Source version must not be empty');
        }
    }

    public static function capture(ImageSourceInterface $source): self
    {
        return new self($source->getReference(), $source->getVersion());
    }

    public function getReference(): SourceReference
    {
        return $this->reference;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getHash(): string
    {
        return hash('sha256', $this->version);
    }

    public function getPath(): string
    {
        return $this->reference->getHash().'/'.$this->getHash();
    }

    public function toArray(): array
    {
        return ['reference' => $this->reference->toArray(), 'version' => $this->version];
    }

    public static function fromArray(array $data): self
    {
        return new self(SourceReference::fromArray($data['reference']), $data['version']);
    }

    public function assertMatches(ImageSourceInterface $source): void
    {
        if ($this->reference->getHash() !== $source->getReference()->getHash() || $this->version !== $source->getVersion()) {
            throw new SourceVersionMismatchException('Source version changed: '.$this->reference->getKey());
        }
    }
}
