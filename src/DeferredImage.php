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

use Contao\Image\Metadata\ImageMetadata;
use Contao\Image\Metadata\MetadataParser;
use Imagine\Image\ImagineInterface;

class DeferredImage extends Image implements DeferredImageInterface
{
    /**
     * @var string|null
     */
    private $sourcePath;

    /**
     * No parent::__construct() call here, as we overwrite the parent
     * constructor to skip the file_exists() checks.
     */
    public function __construct(string $path, ImagineInterface $imagine, ImageDimensions $dimensions, string $sourcePath = null, MetadataParser $metadataParser = null)
    {
        $this->path = $path;
        $this->imagine = $imagine;
        $this->dimensions = $dimensions;
        $this->sourcePath = $sourcePath;
        $this->metadataParser = $metadataParser ?? new MetadataParser();
    }

    public function getMetadata(): ImageMetadata
    {
        if (null === $this->sourcePath) {
            return new ImageMetadata([]);
        }

        if (null === $this->metadata) {
            $this->metadata = $this->metadataParser->parse($this->sourcePath);
        }

        return $this->metadata;
    }
}
