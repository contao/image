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

interface DeferredImageProcessorInterface
{
    public function getResult(TargetIdentity $identity): ?ImageResultInterface;

    public function executeDeferred(TargetIdentity $identity, bool $blocking = true): ?ImageResultInterface;
}
