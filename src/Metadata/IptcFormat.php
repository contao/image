<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Metadata;

use Contao\Image\Exception\InvalidImageMetadataException;

class IptcFormat extends AbstractFormat
{
    public const NAME = 'iptc';
    public const DEFAULT_PRESERVE_KEYS = ['2#116', '2#080', '2#115', '2#110'];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $iptc = [];

        $iptc[116] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#116']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Copyright']
            ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
            ?? $metadata->getFormat(GifFormat::NAME)['Comment']
            ?? []
        );

        $iptc[80] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#080']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['creator']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Artist']
            ?? $metadata->getFormat(PngFormat::NAME)['Author']
            ?? []
        );

        $iptc[115] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#115']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
            ?? $metadata->getFormat(PngFormat::NAME)['Source']
            ?? []
        );

        $iptc[110] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#110']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
            ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
            ?? []
        );

        $iptc[5] = $this->filterValue(
            $metadata->getFormat(self::NAME)['2#005']
            ?? $metadata->getFormat(XmpFormat::NAME)['http://purl.org/dc/elements/1.1/']['title']
            ?? $metadata->getFormat(PngFormat::NAME)['Title']
            ?? []
        );

        $filtered = [];

        foreach ($preserveKeys as $property) {
            if (str_starts_with($property, '2#') && ($key = (int) substr($property, 2)) && isset($iptc[$key])) {
                $filtered[$key] = $iptc[$key];
            }
        }

        return $this->buildIptc($filtered);
    }

    public function parse(string $binaryChunk): array
    {
        $data = @iptcparse("Photoshop 3.0\x00$binaryChunk");

        if (!\is_array($data)) {
            throw new InvalidImageMetadataException('Parsing IPTC metadata failed');
        }

        return $this->toUtf8($data);
    }

    public function toReadable(array $data): array
    {
        unset($data['1#090'], $data['2#000']);

        $keys = array_map(
            static function ($key) {
                return [
                    '2#003' => 'ObjectTypeReference',
                    '2#004' => 'ObjectAttributeReference',
                    '2#005' => 'ObjectName',
                    '2#007' => 'EditStatus',
                    '2#008' => 'EditorialUpdate',
                    '2#010' => 'Urgency',
                    '2#012' => 'SubjectReference',
                    '2#015' => 'Category',
                    '2#020' => 'SupplementalCategories',
                    '2#022' => 'FixtureIdentifier',
                    '2#025' => 'Keywords',
                    '2#026' => 'ContentLocationCode',
                    '2#027' => 'ContentLocationName',
                    '2#030' => 'ReleaseDate',
                    '2#035' => 'ReleaseTime',
                    '2#037' => 'ExpirationDate',
                    '2#038' => 'ExpirationTime',
                    '2#040' => 'SpecialInstructions',
                    '2#042' => 'ActionAdvised',
                    '2#045' => 'ReferenceService',
                    '2#047' => 'ReferenceDate',
                    '2#050' => 'ReferenceNumber',
                    '2#055' => 'DateCreated',
                    '2#060' => 'TimeCreated',
                    '2#062' => 'DigitalCreationDate',
                    '2#063' => 'DigitalCreationTime',
                    '2#065' => 'OriginatingProgram',
                    '2#070' => 'ProgramVersion',
                    '2#075' => 'ObjectCycle',
                    '2#080' => 'By-line',
                    '2#085' => 'By-lineTitle',
                    '2#090' => 'City',
                    '2#092' => 'Sub-location',
                    '2#095' => 'Province-State',
                    '2#100' => 'Country-PrimaryLocationCode',
                    '2#101' => 'Country-PrimaryLocationName',
                    '2#103' => 'OriginalTransmissionReference',
                    '2#105' => 'Headline',
                    '2#110' => 'Credit',
                    '2#115' => 'Source',
                    '2#116' => 'CopyrightNotice',
                    '2#118' => 'Contact',
                    '2#120' => 'Caption-Abstract',
                    '2#121' => 'LocalCaption',
                    '2#122' => 'Writer-Editor',
                    '2#125' => 'RasterizedCaption',
                    '2#130' => 'ImageType',
                    '2#131' => 'ImageOrientation',
                    '2#135' => 'LanguageIdentifier',
                    '2#150' => 'AudioType',
                    '2#151' => 'AudioSamplingRate',
                    '2#152' => 'AudioSamplingResolution',
                    '2#153' => 'AudioDuration',
                    '2#154' => 'AudioOutcue',
                    '2#184' => 'JobID',
                    '2#185' => 'MasterDocumentID',
                    '2#186' => 'ShortDocumentID',
                    '2#187' => 'UniqueDocumentID',
                    '2#188' => 'OwnerID',
                    '2#200' => 'ObjectPreviewFileFormat',
                    '2#201' => 'ObjectPreviewFileVersion',
                    '2#202' => 'ObjectPreviewData',
                    '2#221' => 'Prefs',
                    '2#225' => 'ClassifyState',
                    '2#228' => 'SimilarityIndex',
                    '2#230' => 'DocumentNotes',
                    '2#231' => 'DocumentHistory',
                    '2#232' => 'ExifCameraInfo',
                    '2#255' => 'CatalogSets',
                ][$key] ?? $key;
            },
            array_keys($data)
        );

        return parent::toReadable(array_combine($keys, $data));
    }

    private function buildIptc(array $metadata): string
    {
        $iptc = "\x1C\x01".\chr(90); // 1:90 Coded Character Set
        $iptc .= pack('n', 3);
        $iptc .= "\x1B\x25\x47"; // UTF-8

        foreach ($metadata as $id => $values) {
            foreach ($values as $value) {
                if (!\is_string($value) || '' === $value) {
                    continue;
                }

                if (116 === $id) {
                    $maxlength = 128;
                } elseif (5 === $id) {
                    $maxlength = 64;
                } else {
                    $maxlength = 32;
                }

                $value = substr($value, 0, $maxlength);

                $iptc .= "\x1C\x02".\chr($id);
                $iptc .= pack('n', \strlen($value));
                $iptc .= $value;
            }
        }

        if (\strlen($iptc) < 9) {
            return '';
        }

        // Image resource block
        $irb = '8BIM'; // Signature
        $irb .= "\x04\x04"; // IPTC-IIM Resource ID
        $irb .= "\x00\x00"; // Name
        $irb .= pack('N', \strlen($iptc)); // Size
        $irb .= $iptc;

        return $irb;
    }
}
