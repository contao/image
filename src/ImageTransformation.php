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

final class ImageTransformation
{
    private bool $copy = false;

    public function __construct(
        private ImageInformation $sourceInformation,
        private TransformationCoordinates $coordinates,
        private ProcessingOptions $options,
    ) {
    }

    public function getSourceInformation(): ImageInformation
    {
        return $this->sourceInformation;
    }

    public function getCoordinates(): TransformationCoordinates
    {
        return $this->coordinates;
    }

    public function getOptions(): ProcessingOptions
    {
        return $this->options;
    }

    public function isCopy(): bool
    {
        return $this->copy;
    }

    public function withCopy(bool $copy): self
    {
        $value = clone $this;
        $value->copy = $copy;

        return $value;
    }

    public function getOutputInformation(): ImageInformation
    {
        if ($this->copy) {
            return $this->sourceInformation;
        }
        $format = $this->options->getEncoding()->getFormat() ?? $this->options->getEncoding()->getAdapterOptions('imagine')['format'] ?? $this->sourceInformation->getFormat();

        return new ImageInformation(new Dimensions($this->coordinates->getCropSize()), 'jpeg' === $format ? 'jpg' : $format);
    }

    public function toArray(): array
    {
        return ['source' => $this->sourceInformation->toArray(), 'coordinates' => $this->coordinates->toArray(), 'options' => $this->options->toArray(), 'copy' => $this->copy];
    }

    public static function fromArray(array $data): self
    {
        return (new self(ImageInformation::fromArray($data['source']), TransformationCoordinates::fromArray($data['coordinates']), ProcessingOptions::fromArray($data['options'])))->withCopy($data['copy']);
    }
}
