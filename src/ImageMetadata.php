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

final class ImageMetadata
{
    private int $byteSize = 0;

    /**
     * @internal Use MetadataParser::parse() instead of instantiating this class directly
     */
    public function __construct(private array $xmp, private array $iptc, private array $exif)
    {
    }

    public function getCopyright(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://purl.org/dc/elements/1.1/']['rights']
                ?? $this->iptc['2#116']
                ?? $this->exif['IFD0']['Copyright']
                ?? $this->xmp['http://purl.org/dc/elements/1.1/']['creator']
                ?? $this->iptc['2#080']
                ?? $this->exif['IFD0']['Artist']
                ?? $this->iptc['2#110']
                ?? []
            ),
        );
    }

    public function getCreator(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://purl.org/dc/elements/1.1/']['creator']
                ?? $this->iptc['2#080']
                ?? $this->exif['IFD0']['Artist']
                ?? []
            ),
        );
    }

    public function getSource(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://ns.adobe.com/photoshop/1.0/']['GettyImagesGIFT:AssetID']
                ?? $this->xmp['http://ns.adobe.com/photoshop/1.0/']['Source']
                ?? $this->iptc['2#115']
                ?? $this->iptc['2#005']
                ?? []
            ),
        );
    }

    public function getCredit(): string
    {
        return implode(
            ', ',
            (array) (
                $this->xmp['http://ns.adobe.com/photoshop/1.0/']['Credit']
                ?? $this->iptc['2#110']
                ?? $this->xmp['http://prismstandard.org/namespaces/prismusagerights/2.1/']['creditLine']
                ?? []
            ),
        );
    }

    public function getRawData(): array
    {
        return [
            'xmp' => $this->xmp,
            'iptc' => $this->iptc,
            'exif' => $this->exif,
        ];
    }

    public function getByteSize(): int
    {
        return $this->byteSize;
    }
}
