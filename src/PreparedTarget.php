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

final class PreparedTarget
{
    public function __construct(
        private TargetDescription $description,
        private ImageTransformation $transformation,
        private bool $complete = false,
    ) {
    }

    public function getDescription(): TargetDescription
    {
        return $this->description;
    }

    public function getTransformation(): ImageTransformation
    {
        return $this->transformation;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }

    public function withCompletedDescription(TargetDescription $description): self
    {
        return new self($description, $this->transformation, true);
    }

    public function withPending(): self
    {
        return new self($this->description, $this->transformation);
    }

    public function toArray(): array
    {
        return ['description' => $this->description->toArray(), 'transformation' => $this->transformation->toArray(), 'complete' => $this->complete];
    }

    public static function fromArray(array $data): self
    {
        return new self(TargetDescription::fromArray($data['description']), ImageTransformation::fromArray($data['transformation']), $data['complete']);
    }
}
