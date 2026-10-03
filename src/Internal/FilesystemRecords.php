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

use Contao\Image\Exception\CorruptImageRecordException;
use Contao\Image\Exception\ImageStorageException;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemRecords
{
    public function __construct(private string $root)
    {
    }

    public function path(string $key): string
    {
        if (str_contains($key, '..') || str_contains($key, "\0") || str_contains($key, '\\') || str_starts_with($key, '/')) {
            throw new CorruptImageRecordException('Invalid record key');
        }

        return rtrim($this->root, '/').'/'.$key;
    }

    public function read(string $key): ?array
    {
        $path = $this->path($key);
        $stream = @fopen($path, 'r');

        if (false === $stream) {
            if (!(new Filesystem())->exists($path)) {
                return null;
            }

            throw new ImageStorageException('Unable to read record '.$key);
        }

        try {
            $value = json_decode(stream_get_contents($stream), true, 512, JSON_THROW_ON_ERROR);

            if (!\is_array($value)) {
                throw new CorruptImageRecordException('Invalid record '.$key);
            }

            return $value;
        } catch (\JsonException $exception) {
            throw new CorruptImageRecordException('Corrupt record '.$key, 0, $exception);
        } finally {
            fclose($stream);
        }
    }

    public function write(string $key, array $value): void
    {
        try {
            (new Filesystem())->dumpFile($this->path($key), Json::encode($value));
        } catch (\Throwable $exception) {
            throw new ImageStorageException('Cannot publish record '.$key, 0, $exception);
        }
    }

    public function delete(string $key): void
    {
        (new Filesystem())->remove($this->path($key));
    }

    /**
     * @return iterable<string>
     */
    public function list(string $prefix): iterable
    {
        $path = $this->path($prefix);

        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.json')) {
                yield substr(str_replace('\\', '/', $file->getPathname()), \strlen(rtrim($this->root, '/')) + 1);
            }
        }
    }
}
