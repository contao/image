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

final class PublicFilesystemImageSource extends FilesystemImageSource implements PublicImageSourceInterface
{
    public function __construct(
        string $path,
        SourceReference $reference,
        private string $url,
    ) {
        parent::__construct($path, $reference);
    }

    public function getPublicUrl(): string
    {
        return $this->url;
    }
}
