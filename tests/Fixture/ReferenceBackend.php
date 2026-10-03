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
use Contao\Image\ImageBackendInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\PreparedTarget;
use Contao\Image\TargetDescription;
use Contao\Image\TargetRequest;

final class ReferenceBackend implements ImageBackendInterface
{
    public int $preparations = 0;

    public int $executions = 0;

    public int $deletions = 0;

    public bool $failDelete = false;

    public string $prefix = 'https://images.example';

    public function getIdentifier(): string
    {
        return 'reference';
    }

    public function getCacheNamespace(): string
    {
        return 'reference-v1';
    }

    public function prepare(TargetRequest $request): PreparedTarget
    {
        ++$this->preparations;

        return new PreparedTarget(new TargetDescription('reference', $request->getIdentity()->getPath(), ['source' => $request->getSource()->toArray(), 'coordinates' => $request->getTransformation()->getCoordinates()->toArray()]), $request->getTransformation(), true);
    }

    public function execute(ImageSourceInterface $source, PreparedTarget $target): TargetDescription
    {
        ++$this->executions;

        return $target->getDescription();
    }

    public function getUrl(TargetDescription $description): string
    {
        return $this->prefix.'/'.$description->getKey();
    }

    public function delete(TargetDescription $description): void
    {
        ++$this->deletions;

        if ($this->failDelete) {
            throw new ImageStorageException('Injected deletion failure');
        }
    }
}
