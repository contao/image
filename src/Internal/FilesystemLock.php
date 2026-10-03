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
use Contao\Image\StorageLockInterface;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemLock implements StorageLockInterface
{
    /**
     * @param resource|null $stream
     */
    private function __construct(private $stream)
    {
    }

    public function __destruct()
    {
        $this->release();
    }

    public static function acquire(string $path, bool $exclusive = true, bool $blocking = true): ?self
    {
        (new Filesystem())->mkdir(\dirname($path));
        $stream = @fopen($path, 'c+');

        if (false === $stream) {
            throw new ImageStorageException('Unable to open lock '.$path);
        }

        if (!flock($stream, ($exclusive ? LOCK_EX : LOCK_SH) | ($blocking ? 0 : LOCK_NB))) {
            fclose($stream);

            if ($blocking) {
                throw new ImageStorageException('Unable to acquire lock '.$path);
            }

            return null;
        }

        return new self($stream);
    }

    public function release(): void
    {
        if (null !== $this->stream) {
            flock($this->stream, LOCK_UN);
            fclose($this->stream);
            $this->stream = null;
        }
    }
}
