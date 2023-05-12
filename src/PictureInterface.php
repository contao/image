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

interface PictureInterface
{
    /**
     * Returns the image tag attributes.
     *
     * @return array{src:string,srcset:string,width?:int,height?:int,sizes?:string}
     */
    public function getImg(string $rootDir, string $prefix = ''): array;

    /**
     * @return array{src:ImageInterface,srcset:list<array{ImageInterface,string}>,width?:int,height?:int,sizes?:string}
     */
    public function getRawImg(): array;

    /**
     * Returns the source tags attributes.
     *
     * @return list<array{srcset:string,sizes?:string,media?:string,type?:string}>
     */
    public function getSources(string $rootDir, string $prefix = ''): array;

    /**
     * @return list<array{srcset:list<array{ImageInterface,string}>,sizes?:string,media?:string,type?:string}>
     */
    public function getRawSources(): array;
}
