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
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\Tests\Fixture\MemorySource;
use Symfony\Component\Filesystem\Filesystem;

class StorageRecoveryTest extends AbstractImagePipelineTest
{
    public function testTargetCorruptionReconstructsFromRequest(): void
    {
        $source = $this->source();
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $result = $this->factory->getResizer()->resize($source, $configuration, new ProcessingOptions());
        $path = $this->directory.'/cache/image-v2/targets/'.$result->getIdentity()->getPath().'.json';
        (new Filesystem())->dumpFile($path, '{invalid');
        $restored = $this->factory->getResizer()->resize($source, $configuration, new ProcessingOptions());
        $this->assertSame($result->getIdentity()->getPath(), $restored->getIdentity()->getPath());
        $this->assertCount(1, iterator_to_array($this->factory->getRecords()->listCompleted()));
    }

    public function testLifecycleCorruptionRetainsRecords(): void
    {
        $source = $this->source();
        $configuration = (new ResizeConfiguration())->withWidth(60);
        $this->factory->getResizer()->resize($source, $configuration, new ProcessingOptions());
        $path = $this->directory.'/cache/image-v2/sources/'.$source->getReference()->getHash().'.json';
        (new Filesystem())->dumpFile($path, '{invalid');

        try {
            $this->factory->getLifecycle()->prune(new \DateInterval('PT0S'));
            $this->fail('Expected lifecycle error');
        } catch (ImageStorageException) {
            $this->assertSame('{invalid', file_get_contents($path));
        }
        $this->assertCount(1, iterator_to_array($this->factory->getRecords()->listCompleted()));
    }

    public function testAtomicReplacementKeepsLockIdentity(): void
    {
        $source = $this->source();
        $this->factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(60), new ProcessingOptions());
        $reference = $source->getReference();
        $records = $this->factory->getRecords();
        $other = new FilesystemTargetRecordStorage($this->directory.'/cache/image-v2');
        $lock = $records->lockSource($reference, true);

        try {
            $records->putLifecycle($reference, $records->getLifecycle($reference));
            $this->assertNull($other->lockSource($reference, true, false));
        } finally {
            $lock->release();
        }
        $reacquired = $other->lockSource($reference, true, false);
        $this->assertNotNull($reacquired);
        $reacquired->release();
    }

    public function testInterruptedPublicationKeepsCompleteContent(): void
    {
        $storage = new FilesystemTargetContentStorage($this->directory.'/content', '/images');
        $path = $storage->getPath('export.jpg');
        (new Filesystem())->dumpFile($path, 'previous complete content');
        $source = new MemorySource(str_repeat('x', 140000));
        $source->onRead = static function () use ($source): void {
            if ($source->bytesRead > 70000) {
                throw new ImageStorageException('Injected stream interruption');
            }
        };
        $stream = $source->openStream();

        try {
            $storage->publish('export.jpg', $stream);
            $this->fail('Expected interrupted publication');
        } catch (ImageStorageException) {
            $this->assertSame('previous complete content', file_get_contents($path));
        } finally {
            fclose($stream);
        }
        $this->assertSame(['.', '..', 'export.jpg'], scandir($this->directory.'/content'));
    }
}
