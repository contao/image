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

trigger_deprecation('contao/image', '1.1', 'Using the "ResizeConfigurationInterface" has been deprecated and will no longer work in version 2.0. Use the "Contao\Image\ResizeConfiguration" class instead.');

/**
 * @deprecated Deprecated since version 1.0, to be removed in version 2.0.
 *             Use the Contao\Image\ResizeConfiguration class instead
 */
interface ResizeConfigurationInterface
{
    public const MODE_CROP = 'crop';
    public const MODE_BOX = 'box';
    public const MODE_PROPORTIONAL = 'proportional';
}
