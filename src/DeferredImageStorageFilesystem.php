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

use Contao\Image\Exception\FileNotExistsException;
use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\JsonException;
use Contao\Image\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class DeferredImageStorageFilesystem implements DeferredImageStorageInterface
{
    private const PATH_PREFIX = 'deferred';
    private const PATH_SUFFIX = '.json';

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

        $this->cacheDir = Path::join($cacheDir, self::PATH_PREFIX);
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $path, array $value): void
    {
        $json = json_encode($value);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonException(json_last_error_msg());
        }

        $this->filesystem->dumpFile($this->getConfigPath($path), $json);
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
                throw new RuntimeException(sprintf('Lock for "%s" was already acquired', $path));
            }

            return null;
        }

        $configPath = $this->getConfigPath($path);

        if (!$handle = @fopen($configPath, 'r+') ?: @fopen($configPath, 'r')) {
            throw new FileNotExistsException(sprintf('Unable to open file "%s"', $configPath));
        }

        if (!flock($handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
            fclose($handle);

            if ($blocking) {
                throw new RuntimeException(sprintf('Unable to acquire lock for file "%s"', $configPath));
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
            throw new RuntimeException(sprintf('No acquired lock for "%s" exists', $path));
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
        try {
            $this->filesystem->remove($this->getConfigPath($path));
        } catch (IOException $exception) {
            if (!isset($this->locks[$path])) {
                throw new RuntimeException($exception->getMessage(), 0, $exception);
            }

            $this->releaseLock($path);
            $this->filesystem->remove($this->getConfigPath($path));
        }

        if (isset($this->locks[$path])) {
            $this->releaseLock($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listPaths(): iterable
    {
        if (!$this->filesystem->exists($this->cacheDir)) {
            return new \ArrayIterator([]);
        }

        $iterator = new \RecursiveDirectoryIterator($this->cacheDir);
        $iterator = new \RecursiveIteratorIterator($iterator);

        $iterator = new \CallbackFilterIterator(
            $iterator,
            static function ($path) {
                return self::PATH_SUFFIX === substr((string) $path, -\strlen(self::PATH_SUFFIX));
            }
        );

        return new class($iterator, $this->cacheDir, self::PATH_SUFFIX) extends \IteratorIterator {
            private $cacheDir;
            private $suffix;

            public function __construct(\Traversable $iterator, string $cacheDir, string $suffix)
            {
                parent::__construct($iterator);

                $this->cacheDir = $cacheDir;
                $this->suffix = $suffix;
            }

            public function current(): string
            {
                $path = Path::makeRelative((string) parent::current(), $this->cacheDir);

                return substr($path, 0, -\strlen($this->suffix));
            }
        };
    }

    private function getConfigPath(string $path): string
    {
        if (preg_match('(^/|/$|//|/\.\.|^\.\.)', $path)) {
            throw new InvalidArgumentException(sprintf('Invalid storage key "%s"', $path));
        }

        return Path::join($this->cacheDir, $path.self::PATH_SUFFIX);
    }

    /**
     * Decodes the contents of a stored configuration.
     */
    private function decode(string $contents): array
    {
        $content = json_decode($contents, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonException(json_last_error_msg());
        }

        if (!\is_array($content)) {
            throw new InvalidArgumentException(sprintf('Invalid JSON data: expected array, got "%s"', \gettype($content)));
        }

        return $content;
    }
}
