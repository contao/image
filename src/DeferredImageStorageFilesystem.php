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

    private readonly string $cacheDir;

    private array $locks = [];

    public function __construct(
        string $cacheDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->cacheDir = Path::join($cacheDir, self::PATH_PREFIX);
    }

    public function set(string $path, array $value): void
    {
        try {
            $this->filesystem->dumpFile($this->getConfigPath($path), json_encode($value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function get(string $path): array
    {
        return $this->decode(file_get_contents($this->getConfigPath($path)));
    }

    public function has(string $path): bool
    {
        return $this->filesystem->exists($this->getConfigPath($path));
    }

    public function getLocked(string $path, bool $blocking = true): array|null
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

    public function releaseLock(string $path): void
    {
        if (!isset($this->locks[$path])) {
            throw new RuntimeException(sprintf('No acquired lock for "%s" exists', $path));
        }

        flock($this->locks[$path], LOCK_UN | LOCK_NB);
        fclose($this->locks[$path]);

        unset($this->locks[$path]);
    }

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

    public function listPaths(): iterable
    {
        if (!$this->filesystem->exists($this->cacheDir)) {
            return new \ArrayIterator([]);
        }

        $iterator = new \RecursiveDirectoryIterator($this->cacheDir);
        $iterator = new \RecursiveIteratorIterator($iterator);

        $iterator = new \CallbackFilterIterator(
            $iterator,
            static fn ($path) => str_ends_with((string) $path, self::PATH_SUFFIX)
        );

        return new class($iterator, $this->cacheDir, self::PATH_SUFFIX) extends \IteratorIterator {
            public function __construct(\Traversable $iterator, private readonly string $cacheDir, private readonly string $suffix)
            {
                parent::__construct($iterator);
            }

            public function current(): string
            {
                $path = Path::makeRelative((string) parent::current(), $this->cacheDir);

                return substr($path, 0, -\strlen((string) $this->suffix));
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
        try {
            $content = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage(), $e->getCode(), $e);
        }

        if (!\is_array($content)) {
            throw new InvalidArgumentException(sprintf('Invalid JSON data: expected array, got "%s"', get_debug_type($content)));
        }

        return $content;
    }
}
