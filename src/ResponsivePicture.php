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

final class ResponsivePicture implements ResponsivePictureInterface
{
    public function __construct(private array $img, private array $sources)
    {
    }

    public function getRawImg(): array
    {
        return $this->img;
    }

    public function getRawSources(): array
    {
        return $this->sources;
    }

    public function getImg(): array
    {
        return $this->urls($this->img);
    }

    public function getSources(): array
    {
        return array_map($this->urls(...), $this->sources);
    }

    private function urls(array $attributes): array
    {
        if (isset($attributes['src'])) {
            $attributes['src'] = $attributes['src']->getUrl();
        }
        $attributes['srcset'] = implode(', ', array_map(static fn (array $item): string => $item[0]->getUrl().(isset($item[1]) ? ' '.$item[1] : ''), $attributes['srcset']));

        return $attributes;
    }
}
