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

use Contao\Image\Internal\ImageExporter;

final class FilesystemImageFactory
{
    private SourceInformationProvider $information;

    private TargetCoordinator $targets;

    private ImageLifecycle $lifecycle;

    private ImageResizer $resizer;

    private FilesystemTargetRecordStorage $records;

    public function __construct(FilesystemImageConfiguration $configuration, ImageSourceResolverInterface $resolver, ImageDecoderEncoderInterface $codec)
    {
        $root = rtrim($configuration->getCacheDirectory(), '/').'/image-v2';
        $this->records = new FilesystemTargetRecordStorage($root);
        $information = new FilesystemSourceInformationStorage($root);
        $storage = new FilesystemTargetContentStorage($root.'/content', $configuration->getUrlPrefix());
        $backend = new LocalStoredImageBackend($codec, $storage, $configuration->getNamespace());
        $public = new PublicSourceImageBackend($resolver);
        $backends = new BackendRegistry([$backend, $public]);
        $resolver = new ResultSourceResolver($resolver, $this->records, $backends);
        $this->lifecycle = new ImageLifecycle($this->records, $information, $backends, $resolver);
        $this->targets = new TargetCoordinator($this->records, $backends, $resolver, $this->lifecycle);
        $this->information = new SourceInformationProvider($information, $configuration->getInspector());
        $this->resizer = new ImageResizer($this->information, $this->targets, $this->lifecycle, $backend, $configuration->getSecret(), $public, new ImageExporter([$root]));
    }

    public function getResizer(): ImageResizerInterface
    {
        return $this->resizer;
    }

    public function getDeferredProcessor(): TargetCoordinator
    {
        return $this->targets;
    }

    public function getLifecycle(): ImageLifecycle
    {
        return $this->lifecycle;
    }

    public function getRecords(): TargetRecordStorageInterface
    {
        return $this->records;
    }

    public function getPictureGenerator(): ResponsivePictureGeneratorInterface
    {
        return new ResponsivePictureGenerator($this->resizer, $this->information, $this->lifecycle);
    }
}
