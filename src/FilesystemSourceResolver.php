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

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\SourceUnavailableException;
use Symfony\Component\Filesystem\Path;

final class FilesystemSourceResolver implements ImageSourceResolverInterface
{
    public function __construct(private array $roots, private array $urlPrefixes = [])
    {
    }

    public function resolve(SourceReference $reference, ?string $version = null): ImageSourceInterface
    {
        $root = $this->roots[$reference->getStorage()] ?? null;

        if (null === $root) {
            throw new SourceUnavailableException('Unknown source storage '.$reference->getStorage());
        }
        $path = Path::canonicalize(Path::join($root, $reference->getKey()));

        if (Path::isAbsolute($reference->getKey()) || !Path::isBasePath($root, $path)) {
            throw new InvalidArgumentException('Source key escapes its storage');
        }
        $prefix = $this->urlPrefixes[$reference->getStorage()] ?? null;
        $source = null === $prefix ? new FilesystemImageSource($path, $reference) : new PublicFilesystemImageSource($path, $reference, rtrim($prefix, '/').'/'.str_replace('%2F', '/', rawurlencode($reference->getKey())));

        if (null !== $version) {
            (new SourceVersion($reference, $version))->assertMatches($source);
        }

        return $source;
    }
}
