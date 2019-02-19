<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class DeferredImageStorageFilesystem implements DeferredImageStorageInterface
{
    private const PATH_SUFFIX = '.config';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $locks = [];

    /**
     * @param string          $cacheDir
     * @param Filesystem|null $filesystem
     */
    public function __construct($cacheDir, Filesystem $filesystem = null)
    {
        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $this->cacheDir = (string) $cacheDir;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function set($path, array $value)
    {
        $this->filesystem->dumpFile($this->getConfigPath($path), json_encode($value));
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        return $this->decode(file_get_contents($this->getConfigPath($path)));
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->filesystem->exists($this->getConfigPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getLocked($path)
    {
        if (isset($this->locks[$path])) {
            throw new \RuntimeException(sprintf('Lock for "%s" was already acquired.', $path));
        }

        $configPath = $this->getConfigPath($path);

        if (!$handle = fopen($configPath, 'r+') ?: fopen($configPath, 'r')) {
            throw new \RuntimeException(sprintf('Unable to open file "%s".', $configPath));
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new \RuntimeException(sprintf('Unable to acquire lock for file "%s".', $configPath));
        }

        $this->locks[$path] = $handle;

        return $this->decode(stream_get_contents($handle));
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLock($path)
    {
        if (!isset($this->locks[$path])) {
            throw new \RuntimeException(sprintf('No acquired lock for "%s" exists.', $path));
        }

        flock($this->locks[$path], LOCK_UN | LOCK_NB);
        fclose($this->locks[$path]);

        unset($this->locks[$path]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $this->filesystem->remove($this->getConfigPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function listPaths($limit = -1)
    {
        $iterator = new \RecursiveDirectoryIterator($this->cacheDir);
        $iterator = new \RecursiveIteratorIterator($iterator);
        $iterator = new \CallbackFilterIterator($iterator, function ($path) {
            return substr($path, -\strlen(self::PATH_SUFFIX)) === self::PATH_SUFFIX;
        });
        $iterator = new \LimitIterator($iterator, 0, $limit);

        return array_map(function($path) {
            return substr(Path::makeRelative((string) $path, $this->cacheDir), 0, -\strlen(self::PATH_SUFFIX));
        }, iterator_to_array($iterator));
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getConfigPath($path)
    {
        if (preg_match('(^/|/$|//|/\.\.|^\.\.)', $path)) {
            throw new \InvalidArgumentException(sprintf('Invalid storage key "%s"', $path));
        }

        return $this->cacheDir.'/'.$path.self::PATH_SUFFIX;
    }

    /**
     * Decode the contents of a stored configuration.
     *
     * @param string $contents
     */
    private function decode($contents)
    {
        return json_decode($contents, true);
    }
}
