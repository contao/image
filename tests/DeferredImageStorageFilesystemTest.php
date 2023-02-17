<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\DeferredImageStorageFilesystem;
use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Exception\JsonException;
use Contao\Image\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class DeferredImageStorageFilesystemTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rootDir = Path::canonicalize(__DIR__.'/tmp');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ((new Filesystem())->exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    /**
     * @dataProvider getValues
     */
    public function testHasSetGetDelete(string $key, array $value): void
    {
        $key = 'foo/bar.baz';
        $value = ['foo' => 'bar'];
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->assertFalse($storage->has($key));

        $storage->set($key, $value);

        $this->assertTrue($storage->has($key));
        $this->assertSame($value, $storage->get($key));

        $storage->delete($key);

        $this->assertFalse($storage->has($key));
    }

    /**
     * @dataProvider getValues
     */
    public function testGetLocked(string $key, array $value): void
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);
        $storage->set($key, $value);

        $this->assertSame($value, $storage->getLocked($key));

        $dataPath = Path::join($this->rootDir, 'deferred', $key.'.json');
        $handle = fopen($dataPath, 'r+');

        $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB), 'Data file should be locked');

        $this->assertNull(
            $storage->getLocked($key, false),
            'Self locked file should return null for non-blocking lock.'
        );

        try {
            $storage->getLocked($key, true);
            $this->fail('Self locked file should throw for blocking lock.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/already acquired/', $e->getMessage());
        }

        $storage->releaseLock($key);

        $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB), 'Data file should not be locked');

        $this->assertNull(
            $storage->getLocked($key, false),
            'Foreign locked file should return null for non-blocking lock.'
        );

        flock($handle, LOCK_UN | LOCK_NB);
        fclose($handle);

        $this->assertSame($value, $storage->getLocked($key, false));

        $storage->releaseLock($key);

        $this->expectException(RuntimeException::class);

        $storage->releaseLock($key);
    }

    public function getValues(): \Generator
    {
        yield ['foo', ['foo' => 'bar']];
        yield ['foo/bar.baz', ['foo' => ['nested' => ['array', 0, false]]]];
        yield ['foo/bar/baz/nested/path.jpg', ['foo' => 'bar']];
        yield ['foo.json', ['foo' => 'bar']];
    }

    public function testInvalidJsonThrows(): void
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        if (!is_dir(Path::join($this->rootDir, 'deferred'))) {
            (new Filesystem())->mkdir(Path::join($this->rootDir, 'deferred'));
        }
        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'deferred/test.json'), 'invalid JSON');

        $this->expectException(JsonException::class);

        $storage->get('test');
    }

    public function testInvalidJsonDataThrows(): void
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        if (!is_dir(Path::join($this->rootDir, 'deferred'))) {
            (new Filesystem())->mkdir(Path::join($this->rootDir, 'deferred'));
        }
        (new Filesystem())->dumpFile(Path::join($this->rootDir, 'deferred/test.json'), '"JSON string instead of an array"');

        $this->expectException(InvalidArgumentException::class);

        $storage->get('test');
    }

    public function testInvalidUtf8Throws(): void
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->expectException(JsonException::class);

        $storage->set('foo', ["foo\x80bar"]);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testSetInvalidKeyThrows($key): void
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->expectException(InvalidArgumentException::class);

        $storage->set($key, []);
    }

    public function invalidKeys(): \Generator
    {
        yield ['/foo'];
        yield ['foo/'];
        yield ['foo//bar'];
        yield ['../foo'];
    }

    public function testListPaths(): void
    {
        $originalPaths = [
            'foo1.jpg',
            'foo2.jpg',
            'foo/bar/baz/3.jpg',
            'foo4.jpg',
            'foo5.jpg',
        ];

        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        foreach ($originalPaths as $path) {
            $storage->set($path, []);
        }

        $paths = $storage->listPaths();

        if (!\is_array($paths)) {
            $paths = iterator_to_array($paths);
        }

        sort($originalPaths);
        sort($paths);

        $this->assertSame($originalPaths, $paths);

        $storage = new DeferredImageStorageFilesystem('/path/does/not/exist');

        $this->assertCount(0, $storage->listPaths());
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }
}
