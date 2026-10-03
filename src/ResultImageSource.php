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

use Contao\Image\Exception\SourceUnavailableException;

final class ResultImageSource implements ImageSourceInterface
{
    public const STORAGE = 'image-results';
    private ?ImportantPart $part = null;

    public function __construct(private TargetIdentity $identity, private TargetRecordStorageInterface $records, private BackendRegistry $backends)
    {
    }

    public function getReference(): SourceReference
    {
        return new SourceReference(self::STORAGE, $this->identity->getPath());
    }

    public function getFilename(): string
    {
        return $this->identity->getFilename();
    }

    public function getVersion(): string
    {
        return $this->record()->getRevision();
    }

    public function getImportantPart(): ImportantPart
    {
        return $this->part ?? new ImportantPart();
    }

    public function withImportantPart(ImportantPart $part): self
    {
        $copy = clone $this;
        $copy->part = $part;

        return $copy;
    }

    public function openStream()
    {
        $record = $this->record();

        if (!$record->getTarget()->isComplete()) {
            throw new SourceUnavailableException('Materialize the parent target before resizing it');
        }
        $description = $record->getTarget()->getDescription();
        $backend = $this->backends->get($description->getBackend());

        if (!$backend instanceof ReadableImageBackendInterface) {
            throw new SourceUnavailableException('Result backend is not readable');
        }

        return $backend->openStream($description);
    }

    private function record(): TargetRecord
    {
        return $this->records->get($this->identity) ?? throw new SourceUnavailableException('Parent result was removed');
    }
}
