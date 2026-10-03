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

use Contao\Image\Internal\Streams;

class StoredImageBackend implements ImageBackendInterface
{
    public function __construct(protected ImageDecoderEncoderInterface $codec, protected TargetContentStorageInterface $storage, private string $namespace = 'imagine-v1')
    {
    }

    public function getIdentifier(): string
    {
        return 'stored';
    }

    public function getCacheNamespace(): string
    {
        return $this->namespace;
    }

    public function prepare(TargetRequest $request): PreparedTarget
    {
        return new PreparedTarget(new TargetDescription($this->getIdentifier(), $request->getIdentity()->getPath()), $request->getTransformation());
    }

    public function execute(ImageSourceInterface $source, PreparedTarget $target): TargetDescription
    {
        $version = SourceVersion::capture($source);
        $stream = $target->getTransformation()->isCopy() ? Streams::snapshot($source) : $this->codec->transform($source, $target->getTransformation());

        try {
            $version->assertMatches($source);
            $this->storage->publish($target->getDescription()->getKey(), $stream);
        } finally {
            fclose($stream);
        }

        return $target->getDescription();
    }

    public function getUrl(TargetDescription $description): string
    {
        return $this->storage->getUrl($description->getKey());
    }

    public function delete(TargetDescription $description): void
    {
        $this->storage->delete($description->getKey());
    }
}
