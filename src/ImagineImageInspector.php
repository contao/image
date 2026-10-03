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

use Contao\Image\Exception\ImageDecodingException;
use Contao\Image\Internal\Streams;
use Imagine\Image\ImagineInterface;

final class ImagineImageInspector implements ImageInspectorInterface
{
    public function __construct(private ImagineInterface $imagine)
    {
    }

    public function inspect(ImageSourceInterface $source): ImageInformation
    {
        $stream = Streams::snapshot($source);

        try {
            return $this->inspectPath(stream_get_meta_data($stream)['uri']);
        } finally {
            fclose($stream);
        }
    }

    public function inspectPath(string $path): ImageInformation
    {
        try {
            $image = $this->imagine->open($path);
            $orientation = (int) $image->metadata()->get('ifd0.Orientation', 1);

            if ($orientation < 1 || $orientation > 8) {
                $orientation = 1;
            }
            $size = $image->getSize();
            $width = $size->getWidth();
            $height = $size->getHeight();

            if (\in_array($orientation, [5, 6, 7, 8], true)) {
                [$width, $height] = [$height, $width];
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            $format = ['image/jpeg' => 'jpg', 'image/tiff' => 'tiff', 'image/x-xbitmap' => 'xbm', 'image/vnd.wap.wbmp' => 'wbmp'][$mime] ?? substr($mime, 6);

            return new ImageInformation(new Dimensions(new Size($width, $height), Dimensions::ABSOLUTE, $orientation), $format);
        } catch (\Throwable $exception) {
            throw new ImageDecodingException('Cannot inspect image content', 0, $exception);
        }
    }
}
