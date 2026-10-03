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

final class CountingStream
{
    public $context;
    private MemorySource $source;
    private int $offset = 0;

    public function stream_open(): bool
    {
        $this->source = stream_context_get_options($this->context)['imagefixture']['source'];

        return true;
    }

    public function stream_read(int $count): string
    {
        $bytes = substr($this->source->contents, $this->offset, $count);
        $this->offset += \strlen($bytes);
        $this->source->bytesRead += \strlen($bytes);

        if (null !== $this->source->onRead) {
            ($this->source->onRead)();
        }

        return $bytes;
    }

    public function stream_eof(): bool
    {
        return $this->offset >= \strlen($this->source->contents);
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function stream_close(): void
    {
        ++$this->source->closes;
    }
}
