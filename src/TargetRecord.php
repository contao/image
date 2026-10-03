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

use Contao\Image\Exception\ImageStorageException;

final class TargetRecord
{
    private string $revision;

    public function __construct(private TargetIdentity $identity, private SourceVersion $source, private PreparedTarget $target)
    {
        $this->revision = bin2hex(random_bytes(16));
    }

    public function getIdentity(): TargetIdentity
    {
        return $this->identity;
    }

    public function getSource(): SourceVersion
    {
        return $this->source;
    }

    public function getTarget(): PreparedTarget
    {
        return $this->target;
    }

    public function getRevision(): string
    {
        return $this->revision;
    }

    public function withTarget(PreparedTarget $target): self
    {
        $copy = clone $this;
        $copy->target = $target;

        return $copy;
    }

    public function withPending(): self
    {
        return new self($this->identity, $this->source, $this->target->withPending());
    }

    public function toArray(): array
    {
        return ['schema' => 1, 'identity' => $this->identity->getPath(), 'source' => $this->source->toArray(), 'target' => $this->target->toArray(), 'revision' => $this->revision];
    }

    public static function fromArray(array $data): self
    {
        if (1 !== ($data['schema'] ?? null)) {
            throw new ImageStorageException('Unsupported target record schema');
        }
        $value = new self(new TargetIdentity($data['identity']), SourceVersion::fromArray($data['source']), PreparedTarget::fromArray($data['target']));
        $value->revision = $data['revision'];

        return $value;
    }
}
