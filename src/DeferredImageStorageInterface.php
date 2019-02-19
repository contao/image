<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

interface DeferredImageStorageInterface
{
    public function set($path, array $value);

    public function get($path);

    public function getLocked($path);

    public function releaseLock($path);

    public function delete($path);

    public function has($path);
}
