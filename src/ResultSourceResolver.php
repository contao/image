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

final class ResultSourceResolver implements ImageSourceResolverInterface
{
    public function __construct(
        private ImageSourceResolverInterface $sources,
        private TargetRecordStorageInterface $records,
        private BackendRegistry $backends,
    ) {
    }

    public function resolve(SourceReference $reference, ?string $version = null): ImageSourceInterface
    {
        $source = ResultImageSource::STORAGE === $reference->getStorage() ? new ResultImageSource(new TargetIdentity($reference->getKey()), $this->records, $this->backends) : $this->sources->resolve($reference, $version);

        if (null !== $version) {
            (new SourceVersion($reference, $version))->assertMatches($source);
        }

        return $source;
    }
}
