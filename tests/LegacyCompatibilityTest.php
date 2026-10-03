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

use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;

class LegacyCompatibilityTest extends AbstractImagePipelineTest
{
    public function testExactFilenameAndDeferredRecord(): void
    {
        $fs = new Filesystem();
        (new Imagine())->create(new Box(120, 80))->save($this->directory.'/photo.jpg');
        $fs->touch($this->directory.'/photo.jpg', 1700000000);
        $resizer = new DeferredResizer($this->directory.'/legacy', 'compatibility-secret');
        $result = $resizer->resize(new Image($this->directory.'/photo.jpg', new Imagine()), (new ResizeConfiguration())->setWidth(60), (new ResizeOptions())->setImagineOptions(['jpeg_quality' => 75]));
        $relative = trim(file_get_contents(__DIR__.'/data/legacy-filename.txt'));
        $this->assertSame($this->directory.'/legacy/'.$relative, $result->getPath());
        $this->assertSame(trim(file_get_contents(__DIR__.'/data/legacy-deferred.json')), file_get_contents($this->directory.'/legacy/deferred/'.$relative.'.json'));
        $fs->dumpFile($this->directory.'/legacy/deferred/'.$relative.'.json', file_get_contents(__DIR__.'/data/legacy-deferred.json'));
        $restored = $resizer->getDeferredImage($relative, new Imagine());
        $resizer->resizeDeferredImage($restored);
        $this->assertFileExists($result->getPath());
        $this->assertFileDoesNotExist($this->directory.'/legacy/deferred/'.$relative.'.json');
    }

    public function testQueuedConfigurationSnapshotsLegacyMutation(): void
    {
        $source = $this->source();
        $config = (new ResizeConfiguration())->setWidth(60);
        $result = $this->factory->getResizer()->resize($source, $config, (new ProcessingOptions())->withDeferred(true));
        $config->setWidth(10);
        $completed = $this->factory->getDeferredProcessor()->executeDeferred($result->getIdentity());
        $this->assertSame(60, $completed->getDimensions()->getSize()->getWidth());
    }
}
