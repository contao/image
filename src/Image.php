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

use Contao\Image\Exception\FileNotExistsException;
use Contao\Image\Exception\InvalidArgumentException;
use Contao\ImagineSvg\Image as SvgImage;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Image implements ImageInterface
{
    /**
     * @var string
     *
     * @internal
     */
    protected $path;

    /**
     * @var ImageDimensions
     *
     * @internal
     */
    protected $dimensions;

    /**
     * @var ImagineInterface
     *
     * @internal
     */
    protected $imagine;

    /**
     * @var ImportantPart|null
     */
    private $importantPart;

    public function __construct(string $path, ImagineInterface $imagine, Filesystem $filesystem = null)
    {
        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (!$filesystem->exists($path)) {
            throw new FileNotExistsException($path.' does not exist');
        }

        if (is_dir($path)) {
            throw new FileNotExistsException($path.' is a directory');
        }

        $this->path = $path;
        $this->imagine = $imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function getImagine(): ImagineInterface
    {
        return $this->imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(string $rootDir, string $prefix = ''): string
    {
        if (!Path::isBasePath($rootDir, $this->path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not inside root directory "%s"', $this->path, $rootDir));
        }

        $url = Path::makeRelative($this->path, $rootDir);
        $url = str_replace('%2F', '/', rawurlencode($url));

        return $prefix.$url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions(): ImageDimensions
    {
        if (null === $this->dimensions) {
            // Try getSvgSize() or native exif_read_data()/getimagesize() for better performance
            if ($this->imagine instanceof SvgImagine) {
                $size = $this->getSvgSize();

                if (null !== $size) {
                    $this->dimensions = new ImageDimensions($size);
                }
            } elseif (
                \function_exists('exif_read_data')
                && ($exif = @exif_read_data($this->path, 'COMPUTED,IFD0'))
                && !empty($exif['COMPUTED']['Width'])
                && !empty($exif['COMPUTED']['Height'])
            ) {
                $orientation = $this->fixOrientation($exif['Orientation'] ?? null);
                $size = $this->fixSizeOrientation(new Box($exif['COMPUTED']['Width'], $exif['COMPUTED']['Height']), $orientation);
                $this->dimensions = new ImageDimensions($size, null, null, $orientation);
            } elseif (
                ($size = @getimagesize($this->path))
                && !empty($size[0]) && !empty($size[1])
            ) {
                $this->dimensions = new ImageDimensions(new Box($size[0], $size[1]));
            }

            // Fall back to Imagine
            if (null === $this->dimensions) {
                $imagineImage = $this->imagine->open($this->path);
                $orientation = $this->fixOrientation($imagineImage->metadata()->get('ifd0.Orientation'));
                $size = $this->fixSizeOrientation($imagineImage->getSize(), $orientation);
                $this->dimensions = new ImageDimensions($size, null, null, $orientation);
            }
        }

        return $this->dimensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportantPart(): ImportantPart
    {
        return $this->importantPart ?? new ImportantPart();
    }

    /**
     * {@inheritdoc}
     */
    public function setImportantPart(ImportantPart $importantPart = null): ImageInterface
    {
        $this->importantPart = $importantPart;

        return $this;
    }

    /**
     * Corrects invalid EXIF orientation values.
     */
    private function fixOrientation($orientation): int
    {
        $orientation = (int) $orientation;

        if ($orientation < 1 || $orientation > 8) {
            return ImageDimensions::ORIENTATION_NORMAL;
        }

        return $orientation;
    }

    /**
     * Swaps width and height for (-/+)90 degree rotated orientations.
     */
    private function fixSizeOrientation(BoxInterface $size, int $orientation): BoxInterface
    {
        if (
            \in_array(
                $orientation,
                [
                    ImageDimensions::ORIENTATION_90,
                    ImageDimensions::ORIENTATION_270,
                    ImageDimensions::ORIENTATION_MIRROR_90,
                    ImageDimensions::ORIENTATION_MIRROR_270,
                ],
                true
            )
        ) {
            return new Box($size->getHeight(), $size->getWidth());
        }

        return $size;
    }

    /**
     * Reads the SVG image file partially and returns the size of it.
     *
     * This is faster than reading and parsing the whole SVG file just to get
     * the size of it, especially for large files.
     */
    private function getSvgSize(): ?BoxInterface
    {
        if (!class_exists(SvgImage::class) || !class_exists(\XMLReader::class) || !class_exists(\DOMDocument::class)) {
            return null;
        }

        static $zlibSupport;

        if (null === $zlibSupport) {
            $reader = new \XMLReader();
            $zlibSupport = \in_array('compress.zlib', stream_get_wrappers(), true)
                && true === @$reader->open('compress.zlib://data:text/xml,<x/>')
                && true === @$reader->read()
                && true === @$reader->close();
        }

        $size = null;
        $reader = new \XMLReader();
        $path = $this->path;

        if ($zlibSupport) {
            $path = 'compress.zlib://'.$path;
        }

        $disableEntities = null;

        if (LIBXML_VERSION < 20900) {
            // Enable the entity loader at first to make XMLReader::open() work
            // see https://bugs.php.net/bug.php?id=73328
            $disableEntities = libxml_disable_entity_loader(false);
        }

        $internalErrors = libxml_use_internal_errors(true);

        if ($reader->open($path, null, LIBXML_NONET)) {
            if (LIBXML_VERSION < 20900) {
                // After opening the file disable the entity loader for security reasons
                libxml_disable_entity_loader();
            }

            $size = $this->getSvgSizeFromReader($reader);

            $reader->close();
        }

        if (LIBXML_VERSION < 20900) {
            libxml_disable_entity_loader($disableEntities);
        }

        libxml_use_internal_errors($internalErrors);
        libxml_clear_errors();

        return $size;
    }

    /**
     * Extracts the SVG image size from the given XMLReader object.
     */
    private function getSvgSizeFromReader(\XMLReader $reader): ?BoxInterface
    {
        // Move the pointer to the first element in the document
        while ($reader->read() && \XMLReader::ELEMENT !== $reader->nodeType);

        if (\XMLReader::ELEMENT !== $reader->nodeType || 'svg' !== $reader->name) {
            return null;
        }

        $document = new \DOMDocument();
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
