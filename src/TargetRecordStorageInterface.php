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

interface TargetRecordStorageInterface
{
    public function get(TargetIdentity $identity): ?TargetRecord;

    public function put(TargetRecord $record): void;

    public function delete(TargetIdentity $identity): void;

    /**
     * @return iterable<TargetRecord>
     */
    public function listPending(): iterable;

    /**
     * @return iterable<TargetRecord>
     */
    public function listCompleted(): iterable;

    /**
     * @return iterable<TargetRecord>
     */
    public function listVersion(SourceVersion $source): iterable;

    public function getLifecycle(SourceReference $source): ?array;

    public function putLifecycle(SourceReference $source, array $record): void;

    /**
     * @return iterable<SourceReference>
     */
    public function listSources(): iterable;

    /**
     * @phpstan-impure
     */
    public function lockSource(SourceReference $source, bool $exclusive = false, bool $blocking = true): ?StorageLockInterface;

    /**
     * @phpstan-impure
     */
    public function lockTarget(TargetIdentity $identity, bool $blocking = true): ?StorageLockInterface;
}
