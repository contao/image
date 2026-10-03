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

use Contao\Image\Exception\ImageStorageException;
use Contao\Image\Exception\SourceUnavailableException;
use Contao\Image\Internal\FilesystemRecords;
use Contao\Image\Internal\Streams;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemTargetContentStorage implements LocalTargetContentStorageInterface
{
    private FilesystemRecords $files;

    public function __construct(
        string $root,
        private string $urlPrefix,
    ) {
        $this->files = new FilesystemRecords($root);
    }

    public function getPath(string $key): string
    {
        return $this->files->path($key);
    }

    public function getUrl(string $key): string
    {
        $this->getPath($key);

        return rtrim($this->urlPrefix, '/').'/'.str_replace('%2F', '/', rawurlencode($key));
    }

    public function publish(string $key, $stream): void
    {
        $filesystem = new Filesystem();
        $path = $this->getPath($key);
        $filesystem->mkdir(\dirname($path));
        $temporary = $filesystem->tempnam(\dirname($path), '.image-');
        $output = @fopen($temporary, 'w');

        if (false === $output) {
            $filesystem->remove($temporary);

            throw new ImageStorageException('Cannot open temporary target');
        }

        try {
            while (!feof($stream)) {
                Streams::write($output, Streams::read($stream, 65536));
            }

            if (!fflush($output)) {
                throw new ImageStorageException('Cannot flush target');
            }
            fclose($output);
            $output = null;
            $filesystem->chmod($temporary, 0666, umask());
            // Publish only the complete target
            $filesystem->rename($temporary, $path, true);
        } finally {
            if (null !== $output) {
                fclose($output);
            }
            $filesystem->remove($temporary);
        }
    }

    public function openStream(string $key)
    {
        $stream = @fopen($this->getPath($key), 'r');

        if (false === $stream) {
            throw new SourceUnavailableException('Target content is missing, invalidate its record to regenerate it');
        }

        return $stream;
    }

    public function delete(string $key): void
    {
        $this->files->delete($key);
    }
}
