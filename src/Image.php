<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image;

use Contao\ImagineSvg\Image as SvgImage;
use Contao\ImagineSvg\Imagine as SvgImagine;
use DOMDocument;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Point;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use XMLReader;

/**
 * Image class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Image implements ImageInterface
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CacheItemPoolInterface
     */
    private $dimensionsCache;

    /**
     * @var string
     */
    private $path;

    /**
     * @var ImageDimensionsInterface
     */
    private $dimensions;

    /**
     * @var ImportantPartInterface
     */
    private $importantPart;

    /**
     * Constructor.
     *
     * @param string           $path
     * @param ImagineInterface $imagine
     * @param Filesystem|null  $filesystem
     */
    public function __construct($path, ImagineInterface $imagine, Filesystem $filesystem = null)
    {
        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (!$filesystem->exists($path)) {
            throw new \InvalidArgumentException($path.' does not exist');
        }

        if (is_dir($path)) {
            throw new \InvalidArgumentException($path.' is a directory');
        }

        $this->path = (string) $path;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getImagine()
    {
        return $this->imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function setDimensionsCache(CacheItemPoolInterface $dimensionsCache = null)
    {
        $this->dimensionsCache = $dimensionsCache;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensionsCache()
    {
        return $this->dimensionsCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($rootDir, $prefix = '')
    {
        if (!Path::isBasePath($rootDir, $this->path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not inside root directory "%s"', $this->path, $rootDir));
        }

        $url = Path::makeRelative($this->path, $rootDir);

        $url = str_replace('%2F', '/', rawurlencode($url));

        return $prefix.$url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions()
    {
        $cacheItem = null;

        if (null === $this->dimensions && null !== $this->dimensionsCache) {
            $cacheItem = $this->dimensionsCache->getItem(sha1($this->path . '|' . filemtime($this->path)));
            if ($cacheItem->get() instanceof ImageDimensionsInterface) {
                $this->dimensions = $cacheItem->get();
            }
        }

        if (null === $this->dimensions) {
            // Try getSvgSize() or native getimagesize() for better performance
            if ($this->imagine instanceof SvgImagine) {
                $size = $this->getSvgSize();

                if (null !== $size) {
                    $this->dimensions = new ImageDimensions($size);
                }
            } else {
                $size = @getimagesize($this->path);

                if (!empty($size[0]) && !empty($size[1])) {
                    $this->dimensions = new ImageDimensions(new Box($size[0], $size[1]));
                }
            }

            // Fall back to Imagine
            if (null === $this->dimensions) {
                $this->dimensions = new ImageDimensions($this->imagine->open($this->path)->getSize());
            }
        }

        if ($cacheItem && !$cacheItem->isHit()) {
            $this->dimensionsCache->saveDeferred($cacheItem->set($this->dimensions));
        }

        return $this->dimensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportantPart()
    {
        if (null === $this->importantPart) {
            $this->importantPart = new ImportantPart(new Point(0, 0), $this->getDimensions()->getSize());
        }

        return $this->importantPart;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportantPart(ImportantPartInterface $importantPart = null)
    {
        $this->importantPart = $importantPart;

        return $this;
    }

    /**
     * Reads the SVG image file partially and returns the size of it.
     *
     * This is faster than reading and parsing the whole SVG file just to get
     * the size of it, especially for large files.
     *
     * @return BoxInterface|null
     */
    private function getSvgSize()
    {
        static $zlibSupport;

        if (null === $zlibSupport) {
            $zlibSupport = in_array('compress.zlib', stream_get_wrappers());
        }

        $size = null;
        $reader = new XMLReader();

        $path = $this->path;

        if ($zlibSupport) {
            $path = 'compress.zlib://'.$path;
        }

        // Enable the entity loader at first to make XMLReader::open() work
        // see https://bugs.php.net/bug.php?id=73328
        $disableEntities = libxml_disable_entity_loader(false);
        $internalErrors = libxml_use_internal_errors(true);

        if ($reader->open($path, LIBXML_NONET)) {
            // After opening the file disable the entity loader for security reasons
            libxml_disable_entity_loader(true);

            $size = $this->getSvgSizeFromReader($reader);

            $reader->close();
        }

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);
        libxml_clear_errors();

        return $size;
    }

    /**
     * Extracts the SVG image size from the given XMLReader object.
     *
     * @param XMLReader $reader
     *
     * @return BoxInterface|null
     */
    private function getSvgSizeFromReader(XMLReader $reader)
    {
        // Move the pointer to the first element in the document
        while ($reader->read() && $reader->nodeType !== XMLReader::ELEMENT);

        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'svg') {
            return null;
        }

        $document = new DOMDocument();
        $svg = $document->createElement('svg');
        $document->appendChild($svg);

        foreach (['width', 'height', 'viewBox'] as $key) {
            if ($value = $reader->getAttribute($key)) {
                $svg->setAttribute($key, $value);
            }
        }

        $image = new SvgImage($document, new MetadataBag());

        return $image->getSize();
    }
}
