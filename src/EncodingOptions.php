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

final class EncodingOptions
{
    private array $common = [];
    private array $adapters = [];

    public function withFormat(string $format): self
    {
        $format = strtolower($format);

        if (!preg_match('/^[a-z0-9]+$/D', $format)) {
            throw new InvalidArgumentException('Invalid encoding format');
        }

        return $this->withOption('format', 'jpeg' === $format ? 'jpg' : $format);
    }

    public function withQuality(int $quality): self
    {
        if ($quality < 0 || $quality > 100) {
            throw new InvalidArgumentException('Quality must be between 0 and 100');
        }

        return $this->withOption('quality', $quality);
    }

    public function withPngCompression(int $compression): self
    {
        if ($compression < 0 || $compression > 9) {
            throw new InvalidArgumentException('PNG compression must be between 0 and 9');
        }

        return $this->withOption('png_compression', $compression);
    }

    public function withInterlacing(string $interlacing): self
    {
        if (!\in_array($interlacing, ['none', 'line', 'plane', 'partition'], true)) {
            throw new InvalidArgumentException('Invalid interlacing mode');
        }

        return $this->withOption('interlacing', $interlacing);
    }

    public function withResampling(string $resampling): self
    {
        if (!\in_array($resampling, ['auto', 'nearest', 'box', 'linear', 'cubic', 'lanczos'], true)) {
            throw new InvalidArgumentException('Invalid resampling filter');
        }

        return $this->withOption('resampling', $resampling);
    }

    public function withAdapterOptions(string $adapter, array $options): self
    {
        $copy = clone $this;
        $copy->adapters[$adapter] = Json::normalize($options);

        return $copy;
    }

    public function resolve(string $sourceFormat): self
    {
        $format = $this->common['format'] ?? $this->adapters['imagine']['format'] ?? $sourceFormat;
        $copy = $this->withFormat($format);

        if (!isset($copy->adapters['imagine'])) {
            return $copy;
        }
        $options = $copy->adapters['imagine'];
        unset($options['format']);

        if (isset($copy->common['quality'])) {
            unset($options['quality'], $options[('jpg' === $copy->getFormat() ? 'jpeg' : $copy->getFormat()).'_quality']);

            if ('png' === $copy->getFormat()) {
                unset($options['png_compression_level']);
            }
        }

        foreach (['png_compression' => 'png_compression_level', 'interlacing' => 'interlace', 'resampling' => 'resampling-filter'] as $common => $adapter) {
            if (isset($copy->common[$common])) {
                unset($options[$adapter]);
            }
        }

        if ([] === $options) {
            unset($copy->adapters['imagine']);
        } else {
            $copy->adapters['imagine'] = $options;
        }

        return $copy;
    }

    public function getFormat(): ?string
    {
        return $this->common['format'] ?? null;
    }

    public function getCommonOptions(): array
    {
        return $this->common;
    }

    public function getAdapterOptions(string $adapter): array
    {
        return $this->adapters[$adapter] ?? [];
    }

    public function toArray(): array
    {
        return ['common' => $this->common, 'adapters' => $this->adapters];
    }

    public static function fromArray(array $data): self
    {
        $options = new self();
        $methods = ['format' => 'withFormat', 'quality' => 'withQuality', 'png_compression' => 'withPngCompression', 'interlacing' => 'withInterlacing', 'resampling' => 'withResampling'];

        foreach ($data['common'] as $name => $value) {
            $options = $options->{$methods[$name]}($value);
        }

        foreach ($data['adapters'] as $name => $values) {
            $options = $options->withAdapterOptions($name, $values);
        }

        return $options;
    }

    private function withOption(string $name, mixed $value): self
    {
        $copy = clone $this;
        $copy->common[$name] = $value;

        return $copy;
    }
}
