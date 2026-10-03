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

use Contao\Image\Exception\SourceUnavailableException;

final class PublicSourceImageBackend implements ReadableImageBackendInterface
{
    public function __construct(private ImageSourceResolverInterface $resolver)
    {
    }

    public function getIdentifier(): string
    {
        return 'public-source';
    }

    public function getCacheNamespace(): string
    {
        return 'public-source-v1';
    }

    public function prepare(TargetRequest $request): PreparedTarget
    {
        return new PreparedTarget(new TargetDescription($this->getIdentifier(), $request->getIdentity()->getPath(), $request->getSource()->toArray()), $request->getTransformation(), true);
    }

    public function execute(ImageSourceInterface $source, PreparedTarget $target): TargetDescription
    {
        return $target->getDescription();
    }

    public function getUrl(TargetDescription $description): string
    {
        $source = $this->source($description);

        if (!$source instanceof PublicImageSourceInterface) {
            throw new SourceUnavailableException('Source no longer provides a public URL');
        }

        return $source->getPublicUrl();
    }

    public function openStream(TargetDescription $description)
    {
        return $this->source($description)->openStream();
    }

    public function delete(TargetDescription $description): void
    {
    }

    private function source(TargetDescription $description): ImageSourceInterface
    {
        $version = SourceVersion::fromArray($description->getData());
        $source = $this->resolver->resolve($version->getReference(), $version->getVersion());
        $version->assertMatches($source);

        return $source;
    }
}
