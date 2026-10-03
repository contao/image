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

use Contao\Image\FilesystemImageSource;
use Contao\Image\ImageSourceInterface;
use Contao\Image\SourceVersion;

final class InspectionStream
{
    private $input;

    private $buffer;

    private int $length = 0;

    private int $position = 0;

    private SourceVersion $version;

    public function __construct(private ImageSourceInterface $source)
    {
        $this->version = SourceVersion::capture($source);
        $this->input = $source->openStream();

        try {
            $this->buffer = Streams::temporary();
        } catch (\Throwable $exception) {
            fclose($this->input);

            throw $exception;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @phpstan-impure
     */
    public function read(int $length): string
    {
        $this->ensure($this->position + $length);
        fseek($this->buffer, $this->position);
        $data = Streams::read($this->buffer, min($length, $this->length - $this->position));
        $this->position += \strlen($data);

        return $data;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function skip(int $length): void
    {
        while ($length > 0) {
            $data = $this->read(min(65536, $length));

            if ('' === $data) {
                break;
            }
            $length -= \strlen($data);
        }
    }

    public function getCompletePath(): string
    {
        while (!feof($this->input)) {
            $this->ensure($this->length + 65536);
        }

        return stream_get_meta_data($this->buffer)['uri'];
    }

    public function validate(): void
    {
        $this->version->assertMatches($this->source);

        if ($this->source instanceof FilesystemImageSource) {
            $this->source->validateStream($this->input, $this->version);
        }
    }

    public function close(): void
    {
        if (\is_resource($this->input)) {
            fclose($this->input);
        }

        if (\is_resource($this->buffer)) {
            fclose($this->buffer);
        }
    }

    private function ensure(int $length): void
    {
        while ($this->length < $length && !feof($this->input)) {
            $data = Streams::read($this->input, min(65536, $length - $this->length));
            fseek($this->buffer, 0, SEEK_END);
            Streams::write($this->buffer, $data);
            $this->length += \strlen($data);
        }
    }
}
