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

class XmpFormat extends AbstractFormat
{
    public const NAME = 'xmp';

    public const DEFAULT_PRESERVE_KEYS = [
        'http://purl.org/dc/elements/1.1/' => ['rights', 'creator'],
        'http://ns.adobe.com/photoshop/1.0/' => ['Source', 'Credit'],
    ];

    private const NAMESPACE_ALIAS = [
        'http://purl.org/dc/elements/1.1/' => 'dc',
        'http://ns.adobe.com/photoshop/1.0/' => 'photoshop',
        'http://xmp.gettyimages.com/gift/1.0/' => 'GettyImagesGIFT',
    ];

    public function serialize(ImageMetadata $metadata, array $preserveKeys): string
    {
        $xmp = $metadata->getFormat(self::NAME);

        $xmp['http://purl.org/dc/elements/1.1/']['rights'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://purl.org/dc/elements/1.1/']['rights']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#116']
            ?? $metadata->getFormat(ExifFormat::NAME)['Copyright']
            ?? $metadata->getFormat(PngFormat::NAME)['Copyright']
            ?? $metadata->getFormat(GifFormat::NAME)['Comment']
            ?? []
        );

        $xmp['http://purl.org/dc/elements/1.1/']['creator'] = $this->filterValue(
            $metadata->getFormat(self::NAME)['http://purl.org/dc/elements/1.1/']['creator']
            ?? $metadata->getFormat(IptcFormat::NAME)['2#080']
            ?? $metadata->getFormat(ExifFormat::NAME)['Artist']
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
        $metadata = [];

        foreach ($this->loadXml($binaryChunk)->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'RDF') as $rdf) {
            foreach ($rdf->childNodes ?? [] as $desc) {
                if ('Description' !== $desc->localName || 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' !== $desc->namespaceURI) {
                    continue;
                }

                foreach ($desc->attributes ?? [] as $attr) {
                    $metadata[] = $this->parseValue($attr->namespaceURI, $attr->localName, $attr->value);
                }

                foreach ($desc->childNodes ?? [] as $node) {
                    if ($node instanceof \DOMElement && $node->firstElementChild) {
                        $metadata[] = $this->parseValue($node->namespaceURI, $node->localName, $node->firstElementChild);
                    }
                }
            }
        }

        return $this->toUtf8(array_merge_recursive(...$metadata));
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
                // TODO: support multiple values?
                if ($value = implode(', ', $values)) {
                    $description->setAttributeNS($namespace, self::NAMESPACE_ALIAS[$namespace].':'.$attribute, $value);
                    $empty = false;
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
        if ($value instanceof \DOMElement) {
            if ($value->firstElementChild) {
                $values = [];

                foreach ($value->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'li') as $valueNode) {
                    $values[] = $valueNode->textContent;
                }
            } else {
                $values = [$value->textContent];
            }
        } else {
            $values = [$value];
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
