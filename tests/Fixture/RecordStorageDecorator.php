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

use Contao\Image\Exception\ImageStorageException;
use Contao\Image\SourceReference;
use Contao\Image\SourceVersion;
use Contao\Image\StorageLockInterface;
use Contao\Image\TargetIdentity;
use Contao\Image\TargetRecord;
use Contao\Image\TargetRecordStorageInterface;

class RecordStorageDecorator implements TargetRecordStorageInterface
{
    public ?\Closure $beforeSourceLock = null;
    public array $counts = [];
    public bool $failCompletion = false;
    public ?\Closure $beforeTargetLock = null;

    public function __construct(private TargetRecordStorageInterface $inner)
    {
    }

    public function get(TargetIdentity $identity): ?TargetRecord
    {
        $this->counts['get'] = ($this->counts['get'] ?? 0) + 1;

        return $this->inner->get($identity);
    }

    public function put(TargetRecord $record): void
    {
        $this->counts['put'] = ($this->counts['put'] ?? 0) + 1;

        if ($this->failCompletion && $record->getTarget()->isComplete()) {
            throw new ImageStorageException('Injected completion write failure');
        }
        $this->inner->put($record);
    }

    public function delete(TargetIdentity $identity): void
    {
        $this->counts['delete'] = ($this->counts['delete'] ?? 0) + 1;
        $this->inner->delete($identity);
    }

    public function listPending(): iterable
    {
        $this->counts['listPending'] = ($this->counts['listPending'] ?? 0) + 1;

        return $this->inner->listPending();
    }

    public function listCompleted(): iterable
    {
        $this->counts['listCompleted'] = ($this->counts['listCompleted'] ?? 0) + 1;

        return $this->inner->listCompleted();
    }

    public function listVersion(SourceVersion $source): iterable
    {
        $this->counts['listVersion'] = ($this->counts['listVersion'] ?? 0) + 1;

        return $this->inner->listVersion($source);
    }

    public function getLifecycle(SourceReference $source): ?array
    {
        $this->counts['getLifecycle'] = ($this->counts['getLifecycle'] ?? 0) + 1;

        return $this->inner->getLifecycle($source);
    }

    public function putLifecycle(SourceReference $source, array $record): void
    {
        $this->counts['putLifecycle'] = ($this->counts['putLifecycle'] ?? 0) + 1;
        $this->inner->putLifecycle($source, $record);
    }

    public function listSources(): iterable
    {
        $this->counts['listSources'] = ($this->counts['listSources'] ?? 0) + 1;

        return $this->inner->listSources();
    }

    public function lockSource(SourceReference $source, bool $exclusive = false, bool $blocking = true): ?StorageLockInterface
    {
        $this->counts['lockSource'] = ($this->counts['lockSource'] ?? 0) + 1;

        if (null !== $this->beforeSourceLock) {
            ($this->beforeSourceLock)();
        }

        return $this->inner->lockSource($source, $exclusive, $blocking);
    }

    public function lockTarget(TargetIdentity $identity, bool $blocking = true): ?StorageLockInterface
    {
        $this->counts['lockTarget'] = ($this->counts['lockTarget'] ?? 0) + 1;

        if (null !== $this->beforeTargetLock) {
            ($this->beforeTargetLock)();
        }

        return $this->inner->lockTarget($identity, $blocking);
    }
}
