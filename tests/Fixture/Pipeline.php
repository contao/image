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

use Contao\Image\BackendRegistry;
use Contao\Image\ClockInterface;
use Contao\Image\FilesystemSourceInformationStorage;
use Contao\Image\FilesystemTargetRecordStorage;
use Contao\Image\ImageBackendInterface;
use Contao\Image\ImageLifecycle;
use Contao\Image\ImageResizer;
use Contao\Image\ImageSourceResolverInterface;
use Contao\Image\ResultSourceResolver;
use Contao\Image\SourceInformationProvider;
use Contao\Image\StreamImageInspector;
use Contao\Image\SystemClock;
use Contao\Image\TargetCoordinator;
use Contao\Image\TargetRecordStorageInterface;

final class Pipeline
{
    public readonly ImageResizer $resizer;

    public readonly ImageLifecycle $lifecycle;

    public readonly TargetCoordinator $targets;

    public readonly TargetRecordStorageInterface $records;

    public function __construct(string $root, ImageSourceResolverInterface $resolver, ImageBackendInterface $backend, ?ClockInterface $clock = null, ?TargetRecordStorageInterface $records = null)
    {
        $this->records = $records ?? new FilesystemTargetRecordStorage($root);
        $information = new FilesystemSourceInformationStorage($root);
        $backends = new BackendRegistry([$backend]);
        $resolver = new ResultSourceResolver($resolver, $this->records, $backends);
        $this->lifecycle = new ImageLifecycle($this->records, $information, $backends, $resolver, $clock ?? new SystemClock());
        $this->targets = new TargetCoordinator($this->records, $backends, $resolver, $this->lifecycle);
        $this->resizer = new ImageResizer(new SourceInformationProvider($information, new StreamImageInspector()), $this->targets, $this->lifecycle, $backend, 'test-secret');
    }
}
