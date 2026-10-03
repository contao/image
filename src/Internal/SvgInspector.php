<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Internal;

use Contao\Image\Dimensions;
use Contao\Image\ImageInformation;
use Contao\Image\Size;
use Contao\ImagineSvg\Image;
use Contao\ImagineSvg\SvgBox;
use Imagine\Image\Metadata\MetadataBag;

final class SvgInspector
{
    public function inspect(InspectionStream $stream, bool $compressed): ?ImageInformation
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        $state = new SvgRoot();
        xml_set_element_handler(
            $parser,
            static function ($parser, string $name, array $values) use ($state): void {
                if (!$state->isFound()) {
                    $state->attributes = 'svg' === substr(strrchr(':'.$name, ':'), 1) ? $values : null;
                    $state->found = true;
                }
            },
            static function (): void {}
        );
        xml_set_external_entity_ref_handler($parser, static fn () => false);
        $inflate = $compressed ? inflate_init(ZLIB_ENCODING_GZIP) : null;

        try {
            while (!$state->isFound() && '' !== ($chunk = $stream->read(1024))) {
                $xml = $compressed ? @inflate_add($inflate, $chunk, ZLIB_SYNC_FLUSH) : $chunk;

                if (false === $xml || (!xml_parse($parser, $xml, false) && !$state->isFound())) {
                    return null;
                }
            }

            return null === $state->attributes ? null : $this->information($state->attributes, $compressed);
        } finally {
            unset($parser);
        }
    }

    private function information(array $attributes, bool $compressed): ImageInformation
    {
        $document = new \DOMDocument();
        $svg = $document->createElement('svg');
        $document->appendChild($svg);

        foreach (['width', 'height', 'viewBox'] as $name) {
            if (isset($attributes[$name])) {
                $svg->setAttribute($name, $attributes[$name]);
            }
        }
        // Retain the established SVG unit and aspect-ratio calculations
        $size = (new Image($document, new MetadataBag()))->getSize();
        $kind = match ($size->getType()) {
            SvgBox::TYPE_ASPECT_RATIO => Dimensions::RELATIVE, SvgBox::TYPE_NONE => Dimensions::UNDEFINED, default => Dimensions::ABSOLUTE
        };

        return new ImageInformation(new Dimensions(new Size($size->getWidth(), $size->getHeight()), $kind), $compressed ? 'svgz' : 'svg');
    }
}
