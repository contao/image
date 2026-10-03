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

use Contao\Image\ClockInterface;

final class MutableClock implements ClockInterface
{
    public function __construct(public \DateTimeImmutable $time = new \DateTimeImmutable('2026-01-01T00:00:00Z'))
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->time;
    }
}
