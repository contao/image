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

final class FilesystemTargetRecordStorage implements TargetRecordStorageInterface
{
    private FilesystemRecords $files;

    public function __construct(string $root)
    {
        $this->files = new FilesystemRecords($root);
    }

    public function get(TargetIdentity $identity): ?TargetRecord
    {
        $data = $this->files->read('targets/'.$identity->getPath().'.json');

        if (null === $data) {
            return null;
        }

        try {
            $record = TargetRecord::fromArray($data);

            if ($record->getIdentity()->getPath() !== $identity->getPath()) {
                throw new ImageStorageException('Target identity does not match its record');
            }

            return $record;
        } catch (\Throwable $exception) {
            throw new CorruptImageRecordException('Invalid target record '.$identity->getPath(), 0, $exception);
        }
    }

    public function put(TargetRecord $record): void
    {
        $this->files->write('targets/'.$record->getIdentity()->getPath().'.json', $record->toArray());
    }

    public function delete(TargetIdentity $identity): void
    {
        $this->files->delete('targets/'.$identity->getPath().'.json');
    }

    public function listPending(): iterable
    {
        foreach ($this->records('targets') as $record) {
            if (!$record->getTarget()->isComplete()) {
                yield $record;
            }
        }
    }

    public function listCompleted(): iterable
    {
        foreach ($this->records('targets') as $record) {
            if ($record->getTarget()->isComplete()) {
                yield $record;
            }
        }
    }

    public function listVersion(SourceVersion $source): iterable
    {
        yield from $this->records('targets/'.$source->getPath());
    }

    public function getLifecycle(SourceReference $source): ?array
    {
        $data = $this->files->read('sources/'.$source->getHash().'.json');

        if (null !== $data && (1 !== ($data['schema'] ?? null) || $source->toArray() !== ($data['reference'] ?? null) || !\is_array($data['versions'] ?? null))) {
            throw new ImageStorageException('Invalid lifecycle record');
        }

        if (null !== $data) {
            $this->validateLifecycle($source, $data);
        }

        return $data;
    }

    public function putLifecycle(SourceReference $source, array $record): void
    {
        $this->files->write('sources/'.$source->getHash().'.json', $record);
    }

    public function listSources(): iterable
    {
        foreach ($this->files->list('sources') as $key) {
            $data = $this->files->read($key);

            if (null !== $data) {
                try {
                    $source = SourceReference::fromArray($data['reference']);
                } catch (\Throwable $exception) {
                    throw new ImageStorageException('Invalid lifecycle reference', 0, $exception);
                }
                $this->getLifecycle($source);
                yield $source;
            }
        }
    }

    /**
     * @phpstan-impure
     */
    public function lockSource(SourceReference $source, bool $exclusive = false, bool $blocking = true): ?StorageLockInterface
    {
        return FilesystemLock::acquire($this->files->path('locks/sources/'.$source->getHash().'.lock'), $exclusive, $blocking);
    }

    /**
     * @phpstan-impure
     */
    public function lockTarget(TargetIdentity $identity, bool $blocking = true): ?StorageLockInterface
    {
        return FilesystemLock::acquire($this->files->path('locks/targets/'.$identity->getPath().'.lock'), true, $blocking);
    }

    private function validateLifecycle(SourceReference $source, array $data): void
    {
        if (!\array_key_exists('active', $data) || (null !== $data['active'] && !isset($data['versions'][$data['active']]))) {
            throw new ImageStorageException('Invalid active lifecycle version');
        }

        foreach ($data['versions'] as $hash => $version) {
            if (!\is_string($version['version'] ?? null) || !\is_bool($version['pruning'] ?? null) || !\array_key_exists('stale_at', $version) || (null !== $version['stale_at'] && !\is_int($version['stale_at']))) {
                throw new ImageStorageException('Invalid lifecycle version');
            }

            if ((new SourceVersion($source, $version['version']))->getHash() !== $hash || ($version['pruning'] && null === $version['stale_at'])) {
                throw new ImageStorageException('Inconsistent lifecycle version');
            }
        }
    }

    /**
     * @return iterable<TargetRecord>
     */
    private function records(string $prefix): iterable
    {
        foreach ($this->files->list($prefix) as $key) {
            $record = $this->get(new TargetIdentity(substr($key, 8, -5)));

            if (null !== $record) {
                yield $record;
            }
        }
    }
}
