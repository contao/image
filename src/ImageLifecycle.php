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

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\SourceUnavailableException;

final class ImageLifecycle
{
    public function __construct(
        private TargetRecordStorageInterface $records,
        private SourceInformationStorageInterface $information,
        private BackendRegistry $backends,
        private ImageSourceResolverInterface $resolver,
        private ClockInterface $clock = new SystemClock(),
    ) {
    }

    public function acquire(SourceVersion $source): StorageLockInterface
    {
        while (true) {
            $lock = $this->records->lockSource($source->getReference());

            try {
                $state = $this->records->getLifecycle($source->getReference());
            } catch (\Throwable $exception) {
                $lock->release();

                throw $exception;
            }

            if (null !== $state && $state['active'] === $source->getHash() && !$this->hasPruning($state)) {
                return $lock;
            }
            $lock->release();
            $exclusive = $this->records->lockSource($source->getReference(), true);

            try {
                $this->activate($source);
            } finally {
                $exclusive->release();
            }
        }
    }

    public function acquireExisting(SourceVersion $source, bool $blocking = true): ?StorageLockInterface
    {
        while (true) {
            $lock = $this->records->lockSource($source->getReference(), false, $blocking);

            if (null === $lock) {
                return null;
            }

            try {
                $state = $this->records->getLifecycle($source->getReference());
            } catch (\Throwable $exception) {
                $lock->release();

                throw $exception;
            }

            if (null === $state || !$this->hasPruning($state)) {
                return $lock;
            }
            $lock->release();

            if (!$blocking) {
                return null;
            }
            $exclusive = $this->records->lockSource($source->getReference(), true);

            try {
                $this->pruneSource($source->getReference(), PHP_INT_MIN);
            } finally {
                $exclusive->release();
            }
        }
    }

    public function notifyDeleted(SourceReference $reference): void
    {
        $lock = $this->records->lockSource($reference, true);

        try {
            $state = $this->records->getLifecycle($reference);

            if (null === $state) {
                return;
            }

            foreach ($state['versions'] as &$version) {
                $version['stale_at'] ??= $this->clock->now()->getTimestamp();
            }
            unset($version);
            $state['active'] = null;
            $this->records->putLifecycle($reference, $state);
        } finally {
            $lock->release();
        }
    }

    public function prune(\DateInterval $delay): void
    {
        if ($delay->invert) {
            throw new InvalidArgumentException('Pruning delay must not be negative');
        }
        $cutoff = $this->clock->now()->sub($delay)->getTimestamp();

        foreach ($this->records->listSources() as $source) {
            $lock = $this->records->lockSource($source, true);

            try {
                $this->pruneSource($source, $cutoff);
            } finally {
                $lock->release();
            }
        }
    }

    public function assertUsable(SourceVersion $source): void
    {
        $state = $this->records->getLifecycle($source->getReference());

        if (null === $state || !isset($state['versions'][$source->getHash()]) || $state['versions'][$source->getHash()]['pruning']) {
            throw new SourceUnavailableException('Source version has been claimed for cleanup');
        }
    }

    private function activate(SourceVersion $source): void
    {
        $reference = $source->getReference();
        $state = $this->records->getLifecycle($reference) ?? ['schema' => 1, 'reference' => $reference->toArray(), 'active' => null, 'versions' => []];

        foreach ($state['versions'] as $hash => $version) {
            if ($version['pruning']) {
                $this->cleanVersion($reference, $state, $hash);
            }
        }
        // Opaque hashes have no ordering, so consult the authoritative source
        $source->assertMatches($this->resolver->resolve($reference));

        foreach ($state['versions'] as &$version) {
            $version['stale_at'] ??= $this->clock->now()->getTimestamp();
        }
        unset($version);
        $state['active'] = $source->getHash();
        $state['versions'][$source->getHash()] = ['version' => $source->getVersion(), 'stale_at' => null, 'pruning' => false];
        $this->records->putLifecycle($reference, $state);
    }

    private function pruneSource(SourceReference $reference, int $cutoff): void
    {
        $state = $this->records->getLifecycle($reference);

        if (null === $state) {
            return;
        }

        foreach ($state['versions'] as $hash => $version) {
            if ($version['pruning'] || (null !== $version['stale_at'] && $version['stale_at'] <= $cutoff)) {
                $state['versions'][$hash]['pruning'] = true;
                $this->records->putLifecycle($reference, $state);
                $this->cleanVersion($reference, $state, $hash);
            }
        }
    }

    private function cleanVersion(SourceReference $reference, array &$state, string $hash): void
    {
        $source = new SourceVersion($reference, $state['versions'][$hash]['version']);

        foreach ($this->records->listVersion($source) as $record) {
            $lock = $this->records->lockTarget($record->getIdentity());

            try {
                $description = $record->getTarget()->getDescription();
                $this->backends->get($description->getBackend())->delete($description);
                // Keep the parent record until dependent deletion notification succeeds
                $this->notifyDeleted(new SourceReference(ResultImageSource::STORAGE, $record->getIdentity()->getPath()));
                $this->records->delete($record->getIdentity());
            } finally {
                $lock->release();
            }
        }
        $this->information->delete($source);
        unset($state['versions'][$hash]);

        if ($state['active'] === $hash) {
            $state['active'] = null;
        }
        $this->records->putLifecycle($reference, $state);
    }

    private function hasPruning(array $state): bool
    {
        foreach ($state['versions'] as $version) {
            if ($version['pruning']) {
                return true;
            }
        }

        return false;
    }
}
