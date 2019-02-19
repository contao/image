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
    /**
     * @param string $path
     * @return void
     */
    public function set($path, array $value);

    /**
     * @param string $path
     * @return array
     */
    public function get($path);

    /**
     * @param string $path
     * @return array
     */
    public function getLocked($path);

    /**
     * @param string $path
     * @return void
     */
    public function releaseLock($path);

    /**
     * @param string $path
     * @return void
     */
    public function delete($path);

    /**
     * @param string $path
     * @return bool
     */
    public function has($path);
}
