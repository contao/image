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
use Contao\Image\Internal\Json;
use Contao\Image\Metadata\ExifFormat;
use Contao\Image\Metadata\GifFormat;
use Contao\Image\Metadata\IptcFormat;
use Contao\Image\Metadata\PngFormat;
use Contao\Image\Metadata\XmpFormat;
use Symfony\Component\Filesystem\Path;

final class ProcessingOptions
{
    private EncodingOptions $encoding;

    private bool $deferred = false;

    private bool $bypassCache = false;

    private bool $skipIfDimensionsMatch = false;

    private ?string $destination = null;

    private array $preserveCopyrightMetadata = [XmpFormat::NAME => XmpFormat::DEFAULT_PRESERVE_KEYS, IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS, ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS, PngFormat::NAME => PngFormat::DEFAULT_PRESERVE_KEYS, GifFormat::NAME => GifFormat::DEFAULT_PRESERVE_KEYS];

    public function __construct()
    {
        $this->encoding = new EncodingOptions();
    }

    public function getEncoding(): EncodingOptions
    {
        return $this->encoding;
    }

    public function isDeferred(): bool
    {
        return $this->deferred && !$this->bypassCache && null === $this->destination;
    }

    public function getBypassCache(): bool
    {
        return $this->bypassCache;
    }

    public function getSkipIfDimensionsMatch(): bool
    {
        return $this->skipIfDimensionsMatch;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function getPreserveCopyrightMetadata(): array
    {
        return $this->preserveCopyrightMetadata;
    }

    public function withEncoding(EncodingOptions $encoding): self
    {
        $copy = clone $this;
        $copy->encoding = $encoding;

        return $copy;
    }

    public function withDeferred(bool $deferred): self
    {
        $copy = clone $this;
        $copy->deferred = $deferred;

        return $copy;
    }

    public function withBypassCache(bool $bypass): self
    {
        $copy = clone $this;
        $copy->bypassCache = $bypass;

        return $copy;
    }

    public function withSkipIfDimensionsMatch(bool $skip): self
    {
        $copy = clone $this;
        $copy->skipIfDimensionsMatch = $skip;

        return $copy;
    }

    public function withDestination(?string $path): self
    {
        if (null !== $path && !Path::isAbsolute($path)) {
            throw new InvalidArgumentException('Export destination must be absolute');
        }
        $copy = clone $this;
        $copy->destination = $path;

        return $copy;
    }

    public function withPreserveCopyrightMetadata(array $keys): self
    {
        $copy = clone $this;
        $copy->preserveCopyrightMetadata = Json::normalize($keys);

        return $copy;
    }

    public function toArray(): array
    {
        return ['encoding' => $this->encoding->toArray(), 'copyright' => $this->preserveCopyrightMetadata];
    }

    public static function fromArray(array $data): self
    {
        return (new self())->withEncoding(EncodingOptions::fromArray($data['encoding']))->withPreserveCopyrightMetadata($data['copyright']);
    }
}
