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

final class TargetCoordinator implements DeferredImageProcessorInterface
{
    public function __construct(private TargetRecordStorageInterface $records, private BackendRegistry $backends, private ImageSourceResolverInterface $resolver, private ImageLifecycle $lifecycle)
    {
    }

    public function register(TargetRequest $request, ImageBackendInterface $backend, ImageSourceInterface $source): ImageResultInterface
    {
        $lock = $this->records->lockTarget($request->getIdentity());

        try {
            try {
                $record = $this->records->get($request->getIdentity());
            } catch (CorruptImageRecordException) {
                $record = null;
            }
            $options = $request->getTransformation()->getOptions();

            if (null === $record || $options->getBypassCache()) {
                $record = new TargetRecord($request->getIdentity(), $request->getSource(), $backend->prepare($request));
                $request->getSource()->assertMatches($source);
                $this->records->put($record);
            }

            if (!$record->getTarget()->isComplete() && !$options->isDeferred()) {
                $record = $this->execute($record, $source);
            }

            return $this->result($record);
        } finally {
            $lock->release();
        }
    }

    public function getResult(TargetIdentity $identity): ?ImageResultInterface
    {
        $record = $this->records->get($identity);

        if (null === $record) {
            return null;
        }
        $lock = $this->lifecycle->acquireExisting($record->getSource());

        try {
            $record = $this->records->get($identity);

            return null === $record ? null : $this->result($record);
        } finally {
            $lock->release();
        }
    }

    public function executeDeferred(TargetIdentity $identity, bool $blocking = true): ?ImageResultInterface
    {
        $initial = $this->records->get($identity);

        if (null === $initial) {
            return null;
        }
        $sourceLock = $this->lifecycle->acquireExisting($initial->getSource(), $blocking);

        if (null === $sourceLock) {
            return null;
        }

        try {
            $lock = $this->records->lockTarget($identity, $blocking);

            if (null === $lock) {
                return null;
            }

            try {
                return $this->executeLocked($identity, $blocking);
            } finally {
                $lock->release();
            }
        } finally {
            $sourceLock->release();
        }
    }

    public function invalidate(TargetIdentity $identity): void
    {
        $initial = $this->records->get($identity);

        if (null === $initial) {
            return;
        }
        $sourceLock = $this->lifecycle->acquireExisting($initial->getSource());

        try {
            $lock = $this->records->lockTarget($identity);

            try {
                $record = $this->records->get($identity);

                if (null !== $record) {
                    $this->records->put($record->withPending());
                }
            } finally {
                $lock->release();
            }
        } finally {
            $sourceLock->release();
        }
    }

    public function asSource(TargetIdentity $identity): ImageSourceInterface
    {
        return new ResultImageSource($identity, $this->records, $this->backends);
    }

    public function openStream(TargetIdentity $identity)
    {
        $this->executeDeferred($identity);

        return $this->asSource($identity)->openStream();
    }

    private function executeLocked(TargetIdentity $identity, bool $blocking): ?ImageResultInterface
    {
        $record = $this->records->get($identity);

        if (null === $record) {
            return null;
        }
        $this->lifecycle->assertUsable($record->getSource());

        if ($record->getTarget()->isComplete()) {
            return $blocking ? $this->result($record) : null;
        }
        $source = $this->resolver->resolve($record->getSource()->getReference(), $record->getSource()->getVersion());

        return $this->result($this->execute($record, $source));
    }

    private function execute(TargetRecord $record, ImageSourceInterface $source): TargetRecord
    {
        $record->getSource()->assertMatches($source);
        $backend = $this->backends->get($record->getTarget()->getDescription()->getBackend());
        $description = $backend->execute($source, $record->getTarget());
        $record = $record->withTarget($record->getTarget()->withCompletedDescription($description));
        $this->records->put($record);

        return $record;
    }

    private function result(TargetRecord $record): ImageResultInterface
    {
        $backend = $this->backends->get($record->getTarget()->getDescription()->getBackend());

        if ($backend instanceof LocalImageBackendInterface) {
            return new LocalImageResult($record, $backend, $this);
        }

        if ($backend instanceof ReadableImageBackendInterface) {
            return new ReadableImageResult($record, $backend, $this);
        }

        return new ImageResult($record, $backend);
    }
}
