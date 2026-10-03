<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Fixture;

use Contao\Image\ImageSourceInterface;
use Contao\Image\ImportantPart;
use Contao\Image\SourceReference;

final class MemorySource implements ImageSourceInterface
{
    public ?\Closure $onRead = null;
    public int $opens = 0;
    public int $closes = 0;
    public int $bytesRead = 0;

    public function __construct(public string $contents, private string $filename = 'wrong.txt', private string $key = 'image')
    {
    }

    public function getReference(): SourceReference
    {
        return new SourceReference('memory', $this->key);
    }

    public function getVersion(): string
    {
        return hash('sha256', $this->contents);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getImportantPart(): ImportantPart
    {
        return new ImportantPart();
    }

    public function openStream()
    {
        if (!\in_array('imagefixture', stream_get_wrappers(), true)) {
            stream_wrapper_register('imagefixture', CountingStream::class);
        }
        ++$this->opens;
        $stream = fopen('imagefixture://source', 'r', false, stream_context_create(['imagefixture' => ['source' => $this]]));
        stream_set_chunk_size($stream, 128);

        return $stream;
    }
}
