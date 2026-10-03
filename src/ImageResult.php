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

class ImageResult implements ImageResultInterface
{
    public function __construct(
        protected TargetRecord $record,
        protected ImageBackendInterface $backend,
    ) {
    }

    public function getIdentity(): TargetIdentity
    {
        return $this->record->getIdentity();
    }

    public function getDimensions(): Dimensions
    {
        return $this->record->getTarget()->getTransformation()->getOutputInformation()->getDimensions();
    }

    public function getFormat(): string
    {
        return $this->record->getTarget()->getTransformation()->getOutputInformation()->getFormat();
    }

    public function getUrl(): string
    {
        return $this->backend->getUrl($this->record->getTarget()->getDescription());
    }
}
