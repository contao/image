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

class XmpFormat extends AbstractFormat
{
    public const NAME = 'xmp';

    public const DEFAULT_PRESERVE_KEYS = [
        'http://purl.org/dc/elements/1.1/' => ['rights', 'creator'],
        'http://ns.adobe.com/photoshop/1.0/' => ['Source', 'Credit'],
    ];

    /**
     * @see https://github.com/exiftool/exiftool/blob/624ac0d/lib/Image/ExifTool/XMP.pm
     */
    private const NAMESPACE_ALIAS = [
        'http://ns.adobe.com/exif/1.0/aux/' => 'aux',
        'http://ns.adobe.com/album/1.0/' => 'album',
        'http://creativecommons.org/ns#' => 'cc',
        'http://ns.adobe.com/camera-raw-defaults/1.0/' => 'crd',
        'http://ns.adobe.com/camera-raw-settings/1.0/' => 'crs',
        'http://ns.adobe.com/camera-raw-saved-settings/1.0/' => 'crss',
        'http://purl.org/dc/elements/1.1/' => 'dc',
        'http://ns.adobe.com/exif/1.0/' => 'exif',
        'http://cipa.jp/exif/1.0/' => 'exifEX',
        'http://ns.adobe.com/iX/1.0/' => 'iX',
        'http://ns.adobe.com/pdf/1.3/' => 'pdf',
        'http://ns.adobe.com/pdfx/1.3/' => 'pdfx',
        'http://ns.adobe.com/photoshop/1.0/' => 'photoshop',
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#' => 'rdf',
        'http://www.w3.org/2000/01/rdf-schema#' => 'rdfs',
        'http://ns.adobe.com/xap/1.0/sType/Dimensions#' => 'stDim',
        'http://ns.adobe.com/xap/1.0/sType/ResourceEvent#' => 'stEvt',
        'http://ns.adobe.com/xap/1.0/sType/Font#' => 'stFnt',
        'http://ns.adobe.com/xap/1.0/sType/Job#' => 'stJob',
        'http://ns.adobe.com/xap/1.0/sType/ResourceRef#' => 'stRef',
        'http://ns.adobe.com/xap/1.0/sType/Version#' => 'stVer',
        'http://ns.adobe.com/xap/1.0/sType/ManifestItem#' => 'stMfs',
        'http://ns.adobe.com/photoshop/1.0/camera-profile' => 'stCamera',
        'http://ns.adobe.com/camera-raw-embedded-lens-profile/1.0/' => 'crlcp',
        'http://ns.adobe.com/tiff/1.0/' => 'tiff',
        'adobe:ns:meta' => 'x',
        'http://ns.adobe.com/xap/1.0/g/' => 'xmpG',
        'http://ns.adobe.com/xap/1.0/g/img/' => 'xmpGImg',
        'http://ns.adobe.com/xap/1.0/' => 'xmp',
        'http://ns.adobe.com/xap/1.0/bj/' => 'xmpBJ',
        'http://ns.adobe.com/xmp/1.0/DynamicMedia/' => 'xmpDM',
        'http://ns.adobe.com/xap/1.0/mm/' => 'xmpMM',
        'http://ns.adobe.com/xap/1.0/rights/' => 'xmpRights',
        'http://ns.adobe.com/xmp/note/' => 'xmpNote',
        'http://ns.adobe.com/xap/1.0/t/pg/' => 'xmpTPg',
        'http://ns.adobe.com/xmp/Identifier/qual/1.0/' => 'xmpidq',
        'http://ns.adobe.com/xap/1.0/PLUS/' => 'xmpPLUS',
        'http://ns.optimasc.com/dex/1.0/' => 'dex',
        'http://ns.iview-multimedia.com/mediapro/1.0/' => 'mediapro',
        'http://ns.microsoft.com/expressionmedia/1.0/' => 'expressionmedia',
        'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/' => 'Iptc4xmpCore',
        'http://iptc.org/std/Iptc4xmpExt/2008-02-29/' => 'Iptc4xmpExt',
        'http://ns.microsoft.com/photo/1.0' => 'MicrosoftPhoto',
        'http://ns.microsoft.com/photo/1.1' => 'MP1',
        'http://ns.microsoft.com/photo/1.2/' => 'MP',
        'http://ns.microsoft.com/photo/1.2/t/RegionInfo#' => 'MPRI',
        'http://ns.microsoft.com/photo/1.2/t/Region#' => 'MPReg',
        'http://ns.adobe.com/lightroom/1.0/' => 'lr',
        'http://ns.adobe.com/DICOM/' => 'DICOM',
        'http://www.dji.com/drone-dji/1.0/' => 'drone-dji',
        'http://www.w3.org/2000/svg' => 'svg',
        'http://ns.exiftool.org/1.0/' => 'et',
        'http://ns.useplus.org/ldf/xmp/1.0/' => 'plus',
        'http://prismstandard.org/namespaces/basic/2.0/' => 'prism',
        'http://prismstandard.org/namespaces/prl/2.1/' => 'prl',
        'http://prismstandard.org/namespaces/prismusagerights/2.1/' => 'pur',
        'http://prismstandard.org/namespaces/pmi/2.2/' => 'pmi',
        'http://prismstandard.org/namespaces/prm/3.0/' => 'prm',
        'http://ns.acdsee.com/iptc/1.0/' => 'acdsee',
        'http://www.digikam.org/ns/1.0/' => 'digiKam',
        'http://ns.adobe.com/swf/1.0/' => 'swf',
        'http://developer.sonyericsson.com/cell/1.0/' => 'cell',
        'http://ns.apple.com/adjustment-settings/1.0/' => 'aas',
        'http://www.metadataworkinggroup.com/schemas/regions/' => 'mwg-rs',
        'http://www.metadataworkinggroup.com/schemas/keywords/' => 'mwg-kw',
        'http://www.metadataworkinggroup.com/schemas/collections/' => 'mwg-coll',
        'http://ns.adobe.com/xmp/sType/Area#' => 'stArea',
        'http://ns.extensis.com/extensis/1.0/' => 'extensis',
        'http://ns.idimager.com/ics/1.0/' => 'ics',
        'http://ns.fastpictureviewer.com/fpv/1.0/' => 'fpv',
        'http://ns.adobe.com/creatorAtom/1.0/' => 'creatorAtom',
        'http://ns.apple.com/faceinfo/1.0/' => 'apple-fi',
        'http://ns.google.com/photos/1.0/audio/' => 'GAudio',
        'http://ns.google.com/photos/1.0/image/' => 'GImage',
        'http://ns.google.com/photos/1.0/panorama/' => 'GPano',
        'http://ns.google.com/videos/1.0/spherical/' => 'GSpherical',
        'http://ns.google.com/photos/1.0/depthmap/' => 'GDepth',
        'http://ns.google.com/photos/1.0/focus/' => 'GFocus',
        'http://ns.google.com/photos/1.0/camera/' => 'GCamera',
        'http://ns.google.com/photos/1.0/creations/' => 'GCreations',
        'http://rs.tdwg.org/dwc/index.htm' => 'dwc',
        'http://xmp.gettyimages.com/gift/1.0/' => 'GettyImagesGIFT',
        'http://ns.leiainc.com/photos/1.0/image/' => 'LImage',
        'http://ns.google.com/photos/dd/1.0/profile/' => 'Profile',
        'http://ns.nikon.com/sdc/1.0/' => 'sdc',
        'http://ns.nikon.com/asteroid/1.0/' => 'ast',
        'http://ns.nikon.com/nine/1.0/' => 'nine',
        'http://ns.adobe.com/hdr-metadata/1.0/' => 'hdr_metadata',
    ];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $xmp = $metadata->getFormat(self::NAME);

        $xmp['http://purl.org/dc/elements/1.1/']['rights'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Copyright']
            ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
            ?? $metadata->getFormat(GifFormat::NAME)['Comment']
            ?? []
        );

        $xmp['http://purl.org/dc/elements/1.1/']['creator'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://purl.org/dc/elements/1.1/']['creator']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#080']
            ?? $metadata->getFormat(ExifFormat::NAME)['IFD0']['Artist']
            ?? $metadata->getFormat(PngFormat::NAME)['Author']
            ?? []
        );

        $xmp['http://ns.adobe.com/photoshop/1.0/']['Source'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://ns.adobe.com/photoshop/1.0/']['Source']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#115']
            ?? $metadata->getFormat(PngFormat::NAME)['Source']
            ?? []
        );

        $xmp['http://ns.adobe.com/photoshop/1.0/']['Credit'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://ns.adobe.com/photoshop/1.0/']['Credit']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#110']
            ?? $metadata->getFormat(PngFormat::NAME)['Disclaimer']
            ?? []
        );

        $xmp['http://purl.org/dc/elements/1.1/']['title'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://purl.org/dc/elements/1.1/']['title']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#005']
            ?? $metadata->getFormat(PngFormat::NAME)['Title']
            ?? []
        );

        $filtered = [];

        foreach ($preserveKeys as $namespace => $properties) {
            foreach ($properties as $property) {
                $filtered[$namespace][$property] = $this->filterValue($xmp[$namespace][$property] ?? []);
            }
        }

        return $this->buildXmp($filtered);
    }

    public function parse(string $binaryChunk): array
    {
        $foundDescription = false;
        $metadata = [];

        foreach ($this->loadXml($binaryChunk)->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'RDF') as $rdf) {
            foreach ($rdf->childNodes ?? [] as $desc) {
                if ('Description' !== $desc->localName || 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' !== $desc->namespaceURI) {
                    continue;
                }

                $foundDescription = true;

                foreach ($desc->attributes ?? [] as $attr) {
                    $metadata[] = $this->parseValue($attr->namespaceURI, $attr->localName, $attr->value);
                }

                foreach ($desc->childNodes ?? [] as $node) {
                    if ($node instanceof \DOMElement) {
                        $metadata[] = $this->parseValue($node->namespaceURI, $node->localName, $node);
                    }
                }
            }
        }

        if (!$foundDescription) {
            throw new InvalidImageMetadataException('Parsing XMP metadata failed');
        }

        return $this->toUtf8(array_merge_recursive(...$metadata));
    }

    public function toReadable(array $data): array
    {
        $readable = [];

        foreach ($data as $namespace => $attributes) {
            $prefix = self::NAMESPACE_ALIAS[$namespace] ?? $namespace;

            foreach ($attributes as $attribute => $value) {
                if ('rdf' === $prefix && 'about' === $attribute && !$value) {
                    continue;
                }

                $readable["$prefix:$attribute"] = $value;
            }
        }

        return parent::toReadable($readable);
    }

    private function buildXmp(array $metadata): string
    {
        $dom = $this->loadXml(
            '<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description/>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
        );

        $dom->encoding = 'UTF-8';

        /** @var \DOMElement $description */
        $description = $dom->documentElement->firstChild->firstChild;
        $empty = true;

        foreach ($metadata as $namespace => $attributes) {
            foreach ($attributes as $attribute => $values) {
                if (!$values = array_filter($values, 'strlen')) {
                    continue;
                }

                $empty = false;

                if (1 === \count($values)) {
                    $description->setAttributeNS($namespace, self::NAMESPACE_ALIAS[$namespace].':'.$attribute, implode('', $values));
                    continue;
                }

                $wrap = $dom->createElementNS($namespace, self::NAMESPACE_ALIAS[$namespace].':'.$attribute);
                $bag = $dom->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:Bag');

                $description->appendChild($wrap);
                $wrap->appendChild($bag);

                foreach ($values as $value) {
                    $bag->appendChild(
                        $dom->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:li', $value)
                    );
                }
            }
        }

        if ($empty) {
            return '';
        }

        $xmp = $dom->saveXML($dom->documentElement);

        return "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>$xmp<?xpacket end=\"w\"?>";
    }

    /**
     * @param string|\DOMElement $value
     */
    private function parseValue(string $namespace, string $attr, $value): array
    {
        $values = [];

        if ($value instanceof \DOMElement) {
            foreach ($value->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'li') as $valueNode) {
                $values[] = $valueNode->textContent;
            }

            if (!$values) {
                $values[] = $value->textContent;
            }
        } else {
            $values[] = $value;
        }

        return [$namespace => [$attr => array_values(array_unique(array_filter($values)))]];
    }

    private function loadXml(string $xml): \DOMDocument
    {
        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $disableEntities = null;

        if (LIBXML_VERSION < 20900) {
            $disableEntities = libxml_disable_entity_loader();
        }

        $document = new \DOMDocument();
        $document->loadXML($xml, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (LIBXML_VERSION < 20900) {
            libxml_disable_entity_loader($disableEntities);
        }

        return $document;
    }
}
