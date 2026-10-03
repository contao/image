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

class ReadableImageResult extends ImageResult implements ReadableImageResultInterface
{
    public function __construct(
        TargetRecord $record,
        ReadableImageBackendInterface $backend,
        private TargetCoordinator $coordinator,
    ) {
        parent::__construct($record, $backend);
    }

    public function openStream()
    {
        return $this->coordinator->openStream($this->getIdentity());
    }

    public function asSource(): ImageSourceInterface
    {
        $this->coordinator->executeDeferred($this->getIdentity());

        return $this->coordinator->asSource($this->getIdentity());
    }
}
