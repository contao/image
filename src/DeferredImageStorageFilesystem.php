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

    public function __construct(string $cacheDir, Filesystem $filesystem = null)
    {
        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $this->cacheDir = $cacheDir;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $path, array $value): void
    {
        $this->filesystem->dumpFile($this->getConfigPath($path), json_encode($value));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path): array
    {
        return $this->decode(file_get_contents($this->getConfigPath($path)));
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $path): bool
    {
        return $this->filesystem->exists($this->getConfigPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getLocked(string $path, bool $blocking = true): ?array
    {
        if (isset($this->locks[$path])) {
            if ($blocking) {
                throw new \RuntimeException(sprintf('Lock for "%s" was already acquired.', $path));
            }

            return null;
        }

        $configPath = $this->getConfigPath($path);

        if (!$handle = fopen($configPath, 'rb+') ?: fopen($configPath, 'rb')) {
            throw new \RuntimeException(sprintf('Unable to open file "%s".', $configPath));
        }

        if (!flock($handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
            fclose($handle);

            if ($blocking) {
                throw new \RuntimeException(sprintf('Unable to acquire lock for file "%s".', $configPath));
            }

            return null;
        }

        $this->locks[$path] = $handle;

        return $this->decode(stream_get_contents($handle));
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLock(string $path): void
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
    public function delete(string $path): void
    {
        $this->filesystem->remove($this->getConfigPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function listPaths(int $limit = -1): array
    {
        $iterator = new \RecursiveDirectoryIterator($this->cacheDir);
        $iterator = new \RecursiveIteratorIterator($iterator);

        $iterator = new \CallbackFilterIterator(
            $iterator,
            function ($path) {
                return self::PATH_SUFFIX === substr((string) $path, -\strlen(self::PATH_SUFFIX));
            }
        );

        $iterator = new \LimitIterator($iterator, 0, $limit);

        return array_map(
            function ($path) {
                return substr(Path::makeRelative((string) $path, $this->cacheDir), 0, -\strlen(self::PATH_SUFFIX));
            },
            iterator_to_array($iterator)
        );
    }

    private function getConfigPath(string $path): string
    {
        if (preg_match('(^/|/$|//|/\.\.|^\.\.)', $path)) {
            throw new \InvalidArgumentException(sprintf('Invalid storage key "%s"', $path));
        }

        return $this->cacheDir.'/'.$path.self::PATH_SUFFIX;
    }

    /**
     * Decode the contents of a stored configuration.
     */
    private function decode(string $contents): array
    {
        return json_decode($contents, true);
    }
}
