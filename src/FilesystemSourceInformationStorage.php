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

use Contao\Image\Exception\CorruptImageRecordException;
use Contao\Image\Exception\ImageStorageException;
use Contao\Image\Internal\FilesystemLock;
use Contao\Image\Internal\FilesystemRecords;

final class FilesystemSourceInformationStorage implements SourceInformationStorageInterface
{
    private FilesystemRecords $files;

    public function __construct(string $root)
    {
        $this->files = new FilesystemRecords($root);
    }

    public function get(SourceVersion $source): ?ImageInformation
    {
        try {
            $record = $this->files->read('information/'.$source->getPath().'.json');

            if (1 !== ($record['schema'] ?? null) || ($record['source'] ?? null) !== $source->toArray()) {
                return null;
            }

            return ImageInformation::fromArray($record['information']);
        } catch (ImageStorageException $exception) {
            if (!$exception instanceof CorruptImageRecordException) {
                throw $exception;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function put(SourceVersion $source, ImageInformation $information): void
    {
        $this->files->write('information/'.$source->getPath().'.json', ['schema' => 1, 'source' => $source->toArray(), 'information' => $information->toArray()]);
    }

    public function delete(SourceVersion $source): void
    {
        $this->files->delete('information/'.$source->getPath().'.json');
    }

    public function lock(SourceVersion $source): StorageLockInterface
    {
        return FilesystemLock::acquire($this->files->path('locks/information/'.$source->getPath().'.lock'));
    }
}
