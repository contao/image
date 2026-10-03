<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\Exception\ImageStorageException;
use Contao\Image\FilesystemTargetContentStorage;
use Contao\Image\FilesystemTargetRecordStorage;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalImageResultInterface;
use Contao\Image\LocalStoredImageBackend;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Contao\Image\Tests\Fixture\Pipeline;
use Contao\Image\Tests\Fixture\RecordStorageDecorator;
use Contao\Image\Tests\Fixture\Worker;
use Imagine\Gd\Imagine;

class ConcurrencyTest extends AbstractImagePipelineTest
{
    public function testConcurrentInspectionWaitsAndReuses(): void
    {
        $this->source();
        $first = new Worker('inspection', $this->directory);
        $first->expect('ready');
        $first->expect('locking');
        $first->expect('inspect');
        $second = new Worker('inspection', $this->directory);
        $second->expect('ready');
        $second->expect('locking');
        $first->release();
        $first->expect('done');
        $second->expect('done');
        $first->finish();
        $second->finish();
        $this->assertSame("inspection\n", file_get_contents($this->directory.'/inspections'));
    }

    public function testFailedInspectionReleasesWaiters(): void
    {
        $this->source();
        $first = new Worker('inspection-fail', $this->directory);
        $first->expect('ready');
        $first->expect('locking');
        $first->expect('inspect');
        $second = new Worker('inspection', $this->directory);
        $second->expect('ready');
        $second->expect('locking');
        $first->release();
        $first->expect('failed');
        $second->expect('inspect');
        $second->release();
        $second->expect('done');
        $first->finish();
        $second->finish();
        $this->assertSame("inspection\n", file_get_contents($this->directory.'/inspections'));
    }

    public function testConcurrentTargetExecutionPublishesOnce(): void
    {
        [$pipeline, $result] = $this->pendingTarget();
        $first = $this->blockedTarget($result->getIdentity()->getPath());
        $second = new Worker('target', $this->directory, $result->getIdentity()->getPath());
        $second->expect('ready');
        $second->expect('target-lock');
        $this->assertNull($pipeline->targets->executeDeferred($result->getIdentity(), false));
        $this->assertFileDoesNotExist($result->getPath());
        $first->release();
        $first->expect('done');
        $second->expect('done');
        $first->finish();
        $second->finish();
        $this->assertSame("transform\n", file_get_contents($this->directory.'/transformations'));
        $this->assertSame([60, 40], \array_slice(getimagesize($result->getPath()), 0, 2));
        $this->assertCount(1, iterator_to_array($pipeline->records->listCompleted()));
    }

    public function testProcessDeathReleasesIndependentLock(): void
    {
        [$pipeline, $result] = $this->pendingTarget();
        $first = $this->blockedTarget($result->getIdentity()->getPath());
        $second = new Worker('target', $this->directory, $result->getIdentity()->getPath());
        $second->expect('ready');
        $second->expect('target-lock');
        $first->kill();
        $second->expect('transform');
        $second->release();
        $second->expect('done');
        $second->finish();
        $this->assertSame("transform\n", file_get_contents($this->directory.'/transformations'));
        $this->assertCount(1, iterator_to_array($pipeline->records->listCompleted()));
    }

    public function testPublicationBeforeCompletionIsRetryable(): void
    {
        $source = $this->source();
        $records = new RecordStorageDecorator(new FilesystemTargetRecordStorage($this->directory.'/race'));
        $backend = new LocalStoredImageBackend(new ImagineImageDecoderEncoder(new Imagine()), new FilesystemTargetContentStorage($this->directory.'/content', '/images'));
        $pipeline = new Pipeline($this->directory.'/race', $this->resolver, $backend, null, $records);
        $result = $pipeline->resizer->resize($source, (new ResizeConfiguration())->withWidth(60), (new ProcessingOptions())->withDeferred(true));
        $this->assertInstanceOf(LocalImageResultInterface::class, $result);
        $records->failCompletion = true;

        try {
            $pipeline->targets->executeDeferred($result->getIdentity());
            $this->fail('Expected completion failure');
        } catch (ImageStorageException) {
            $this->assertFileExists($result->getPath());
            $this->assertCount(1, iterator_to_array($records->listPending()));
        }
        $records->failCompletion = false;
        $pipeline->targets->executeDeferred($result->getIdentity());
        $this->assertCount(1, iterator_to_array($records->listCompleted()));
    }

    /**
     * @dataProvider cleanupModes
     */
    public function testPruningCoordinatesRegeneration(bool $interrupt): void
    {
        [$pipeline, $result] = $this->pendingTarget();
        $pipeline->targets->executeDeferred($result->getIdentity());
        $reference = new SourceReference('assets', 'source');
        $pipeline->lifecycle->notifyDeleted($reference);
        $pruner = new Worker('prune', $this->directory);
        $pruner->expect('ready');
        $pruner->expect('target-lock');
        $pruner->expect('delete');
        $generator = new Worker('regenerate', $this->directory);
        $generator->expect('ready');
        $generator->expect('source-lock');
        $this->assertNull($pipeline->targets->executeDeferred($result->getIdentity(), false));

        if ($interrupt) {
            $pruner->kill();
        } else {
            $pruner->release();
            $pruner->expect('done');
            $pruner->finish();
        }
        $generator->expect('transform');
        $generator->release();
        $generator->expect('done');
        $generator->finish();
        $this->assertCount(1, iterator_to_array($pipeline->records->listCompleted()));
        $this->assertFileExists($result->getPath());
        $lock = $pipeline->records->lockSource($reference, true, false);
        $this->assertNotNull($lock);
        $lock->release();
    }

    public static function cleanupModes(): iterable
    {
        yield 'completed cleanup' => [false];
        yield 'interrupted cleanup' => [true];
    }

    public function testDuplicateRegistrationUsesOneTarget(): void
    {
        $this->source();
        $first = new Worker('register', $this->directory);
        $first->expect('ready');
        $first->expect('target-lock');
        $first->expect('transform');
        $second = new Worker('register', $this->directory);
        $second->expect('ready');
        $second->expect('target-lock');
        $first->release();
        $first->expect('done');
        $second->expect('done');
        $first->finish();
        $second->finish();
        $this->assertSame("transform\n", file_get_contents($this->directory.'/transformations'));
        $this->assertCount(1, iterator_to_array((new FilesystemTargetRecordStorage($this->directory.'/race'))->listCompleted()));
    }

    private function pendingTarget(): array
    {
        $source = $this->source();
        $backend = new LocalStoredImageBackend(new ImagineImageDecoderEncoder(new Imagine()), new FilesystemTargetContentStorage($this->directory.'/content', '/images'));
        $pipeline = new Pipeline($this->directory.'/race', $this->resolver, $backend);

        return [$pipeline, $pipeline->resizer->resize($source, (new ResizeConfiguration())->withWidth(60), (new ProcessingOptions())->withDeferred(true))];
    }

    private function blockedTarget(string $identity): Worker
    {
        $worker = new Worker('target', $this->directory, $identity);
        $worker->expect('ready');
        $worker->expect('target-lock');
        $worker->expect('transform');

        return $worker;
    }
}
