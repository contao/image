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
use Contao\Image\Exception\SourceVersionMismatchException;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceVersion;
use Contao\Image\Tests\Fixture\MemoryResolver;
use Contao\Image\Tests\Fixture\MemorySource;
use Contao\Image\Tests\Fixture\MutableClock;
use Contao\Image\Tests\Fixture\Pipeline;
use Contao\Image\Tests\Fixture\ReferenceBackend;

class ImageLifecycleTest extends AbstractImagePipelineTest
{
    public function testUrlOnlyAndWarmCacheHaveNoContentReads(): void
    {
        $this->source();
        $source = new MemorySource(file_get_contents($this->directory.'/source'));
        $backend = new ReferenceBackend();
        $pipeline = new Pipeline($this->directory.'/records', new MemoryResolver($source), $backend);
        $config = (new ResizeConfiguration())->withWidth(60);
        $result = $pipeline->resizer->resize($source, $config, (new ProcessingOptions())->withDeferred(true));
        $this->assertSame(0, $backend->executions);
        $this->assertSame(1, $source->opens);
        $backend->prefix = 'https://new.example';
        $reads = $source->bytesRead;
        $warm = $pipeline->resizer->resize($source, $config, new ProcessingOptions());
        $this->assertSame(1, $backend->preparations);
        $this->assertSame($reads, $source->bytesRead);
        $this->assertSame(1, $source->opens);
        $this->assertSame($result->getIdentity()->getPath(), $warm->getIdentity()->getPath());
        $this->assertStringStartsWith($backend->prefix, $warm->getUrl());
        $record = $pipeline->records->get($result->getIdentity());
        $this->assertSame([60, 40], $record->getTarget()->getDescription()->getData()['coordinates']['crop']);
        $this->assertStringNotContainsString('https://', json_encode($record->toArray()));
    }

    public function testStaleDelayRestorationAndOldRequests(): void
    {
        $this->source();
        $old = new MemorySource(file_get_contents($this->directory.'/source'));
        $new = new MemorySource($old->contents.'new-version');
        $resolver = new MemoryResolver($old);
        $clock = new MutableClock();
        $backend = new ReferenceBackend();
        $pipeline = new Pipeline($this->directory.'/records', $resolver, $backend, $clock);
        $config = (new ResizeConfiguration())->withWidth(60);
        $first = $pipeline->resizer->resize($old, $config, new ProcessingOptions());
        $resolver->source = $new;
        $second = $pipeline->resizer->resize($new, $config, new ProcessingOptions());
        $this->assertNotSame($first->getIdentity()->getPath(), $second->getIdentity()->getPath());

        try {
            $pipeline->resizer->resize($old, $config, new ProcessingOptions());
            $this->fail('Expected old request rejection');
        } catch (SourceVersionMismatchException) {
            $this->assertSame(SourceVersion::capture($new)->getHash(), $pipeline->records->getLifecycle($old->getReference())['active']);
        }
        $resolver->source = $old;
        $restored = $pipeline->resizer->resize($old, $config, new ProcessingOptions());
        $this->assertSame($first->getIdentity()->getPath(), $restored->getIdentity()->getPath());
        $this->assertSame(2, $backend->preparations);
        $clock->time = $clock->time->modify('+29 days');
        $pipeline->lifecycle->prune(new \DateInterval('P30D'));
        $this->assertSame(0, $backend->deletions);
        $clock->time = $clock->time->modify('+1 day');
        $pipeline->lifecycle->prune(new \DateInterval('P30D'));
        $this->assertSame(1, $backend->deletions);
        $this->assertNotNull($pipeline->records->get($first->getIdentity()));
        $this->assertNull($pipeline->records->get($second->getIdentity()));
    }

    public function testRepeatedDeletionAndInterruptedCleanup(): void
    {
        $this->source();
        $source = new MemorySource(file_get_contents($this->directory.'/source'));
        $clock = new MutableClock();
        $backend = new ReferenceBackend();
        $pipeline = new Pipeline($this->directory.'/records', new MemoryResolver($source), $backend, $clock);
        $config = (new ResizeConfiguration())->withWidth(60);
        $first = $pipeline->resizer->resize($source, $config, new ProcessingOptions());
        $pipeline->lifecycle->notifyDeleted($source->getReference());
        $clock->time = $clock->time->modify('+30 days');
        $pipeline->lifecycle->notifyDeleted($source->getReference());
        $backend->failDelete = true;

        try {
            $pipeline->lifecycle->prune(new \DateInterval('P30D'));
            $this->fail('Expected deletion failure');
        } catch (ImageStorageException) {
            $this->assertTrue($pipeline->records->getLifecycle($source->getReference())['versions'][SourceVersion::capture($source)->getHash()]['pruning']);
        }
        $this->assertNotNull($pipeline->records->get($first->getIdentity()));
        $backend->failDelete = false;
        $regenerated = $pipeline->resizer->resize($source, $config, new ProcessingOptions());
        $this->assertSame(2, $backend->preparations);
        $this->assertSame(2, $backend->deletions);
        $this->assertSame($first->getIdentity()->getPath(), $regenerated->getIdentity()->getPath());
        $this->assertNotNull($pipeline->records->get($regenerated->getIdentity()));
    }
}
