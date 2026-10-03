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

interface ImageBackendInterface
{
    public function getIdentifier(): string;

    public function getCacheNamespace(): string;

    public function prepare(TargetRequest $request): PreparedTarget;

    public function execute(ImageSourceInterface $source, PreparedTarget $target): TargetDescription;

    public function getUrl(TargetDescription $description): string;

    public function delete(TargetDescription $description): void;
}
