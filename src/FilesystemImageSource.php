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

use Contao\Image\Exception\SourceUnavailableException;
use Contao\Image\Exception\SourceVersionMismatchException;

class FilesystemImageSource implements ImageSourceInterface
{
    private ?ImportantPart $importantPart = null;

    public function __construct(private string $path, private SourceReference $reference)
    {
    }

    public function getReference(): SourceReference
    {
        return $this->reference;
    }

    public function getFilename(): string
    {
        return basename($this->path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getImportantPart(): ImportantPart
    {
        return $this->importantPart ?? new ImportantPart();
    }

    public function withImportantPart(ImportantPart $part): self
    {
        $copy = clone $this;
        $copy->importantPart = $part;

        return $copy;
    }

    public function getVersion(): string
    {
        clearstatcache(true, $this->path);
        $stat = @stat($this->path);

        if (false === $stat || !is_file($this->path)) {
            throw new SourceUnavailableException('Cannot read source '.$this->path);
        }

        return $this->fingerprint($stat);
    }

    public function openStream()
    {
        $version = $this->getVersion();
        $stream = @fopen($this->path, 'r');

        if (false === $stream) {
            throw new SourceUnavailableException('Cannot open source '.$this->path);
        }

        try {
            $this->validateStream($stream, new SourceVersion($this->reference, $version));

            return $stream;
        } catch (\Throwable $exception) {
            fclose($stream);

            throw $exception;
        }
    }

    /**
     * @param resource $stream
     */
    public function validateStream($stream, SourceVersion $version): void
    {
        $stat = fstat($stream);

        if (false === $stat || $version->getVersion() !== $this->fingerprint($stat)) {
            throw new SourceVersionMismatchException('Opened source changed '.$this->path);
        }
        $version->assertMatches($this);
    }

    private function fingerprint(array $stat): string
    {
        return hash('sha256', implode(':', [$this->reference->getHash(), $stat['dev'], $stat['ino'], $stat['size'], $stat['mtime'], $stat['ctime']]));
    }
}
