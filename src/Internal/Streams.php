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

use Contao\Image\Exception\ImageStorageException;
use Contao\Image\FilesystemImageSource;
use Contao\Image\ImageSourceInterface;
use Contao\Image\SourceVersion;

final class Streams
{
    /**
     * @return resource
     */
    public static function temporary()
    {
        $stream = tmpfile();

        if (false === $stream) {
            throw new ImageStorageException('Unable to create temporary image stream');
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    public static function write($stream, string $bytes): void
    {
        while ('' !== $bytes) {
            $written = fwrite($stream, $bytes);

            if (false === $written || 0 === $written) {
                throw new ImageStorageException('Unable to write image stream');
            }
            $bytes = substr($bytes, $written);
        }
    }

    /**
     * @param resource $stream
     */
    public static function read($stream, int $length): string
    {
        $bytes = '';

        while (\strlen($bytes) < $length && !feof($stream)) {
            $chunk = fread($stream, $length - \strlen($bytes));

            if (false === $chunk || ('' === $chunk && !feof($stream))) {
                throw new ImageStorageException('Unable to read image stream');
            }
            $bytes .= $chunk;
        }

        return $bytes;
    }

    /**
     * @return resource
     */
    public static function snapshot(ImageSourceInterface $source)
    {
        $version = SourceVersion::capture($source);
        $input = $source->openStream();
        $output = null;

        try {
            $output = self::temporary();

            while (!feof($input)) {
                self::write($output, self::read($input, 65536));
            }
            $version->assertMatches($source);

            if ($source instanceof FilesystemImageSource) {
                $source->validateStream($input, $version);
            }
            rewind($output);

            return $output;
        } catch (\Throwable $exception) {
            if (\is_resource($output)) {
                fclose($output);
            }

            throw $exception;
        } finally {
            fclose($input);
        }
    }
}
