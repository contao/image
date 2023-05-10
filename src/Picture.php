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

class Picture implements PictureInterface
{
    public function __construct(
        private readonly array $img,
        private readonly array $sources,
    ) {
        $this->validateSrcAttribute($img);
        $this->validateSrcsetAttribute($img);

        foreach ($sources as $source) {
            $this->validateSrcsetAttribute($source);
        }
    }

    public function getImg(string $rootDir = null, string $prefix = ''): array
    {
        if (null === $rootDir) {
            if ('' !== $prefix) {
                throw new InvalidArgumentException(sprintf('Prefix must no be specified if rootDir is null, given "%s"', $prefix));
            }

            return $this->img;
        }

        return $this->buildUrls($this->img, $rootDir, $prefix);
    }

    public function getSources(string $rootDir = null, string $prefix = ''): array
    {
        if (null === $rootDir) {
            if ('' !== $prefix) {
                throw new InvalidArgumentException(sprintf('Prefix must no be specified if rootDir is null, given "%s"', $prefix));
            }

            return $this->sources;
        }

        return array_map(
            fn ($source) => $this->buildUrls($source, $rootDir, $prefix),
            $this->sources
        );
    }

    /**
     * Converts image objects in an attributes array to URLs.
     *
     * @param array{src:ImageInterface|null, srcset:list<array{0:ImageInterface, 1:string}>} $img
     */
    private function buildUrls(array $img, string $rootDir, string $prefix): array
    {
        if (isset($img['src'])) {
            $img['src'] = $img['src']->getUrl($rootDir, $prefix);
        }

        $img['srcset'] = array_map(
            /** @param array{0:ImageInterface, 1:string} $src */
            static function (array $src) use ($rootDir, $prefix) {
                $src[0] = $src[0]->getUrl($rootDir, $prefix);

                return implode(' ', $src);
            },
            $img['srcset']
        );

        $img['srcset'] = implode(', ', $img['srcset']);

        return $img;
    }

    /**
     * Validates the src attribute.
     */
    private function validateSrcAttribute(array $img): void
    {
        if (!isset($img['src'])) {
            throw new InvalidArgumentException('Missing src attribute');
        }

        if (!$img['src'] instanceof ImageInterface) {
            throw new InvalidArgumentException('Src must be of type ImageInterface');
        }
    }

    /**
     * Validates the srcset attribute.
     */
    private function validateSrcsetAttribute(array $img): void
    {
        if (!isset($img['srcset'])) {
            throw new InvalidArgumentException('Missing srcset attribute');
        }

        foreach ($img['srcset'] as $src) {
            if (!$src[0] instanceof ImageInterface) {
                throw new InvalidArgumentException('Srcsets must be of type ImageInterface');
            }
        }
    }
}
