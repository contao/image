<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredImageStorageFilesystem;
use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeCalculatorInterface;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class DeferredImageStorageFilesystemTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->rootDir = __DIR__.'/tmp';
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->rootDir)) {
            (new Filesystem())->remove($this->rootDir);
        }
    }

    public function testInstantiation()
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->assertInstanceOf('Contao\Image\DeferredImageStorageFilesystem', $storage);
        $this->assertInstanceOf('Contao\Image\DeferredImageStorageInterface', $storage);
    }

    /**
     * @dataProvider getValues
     */
    public function testHasSetGetDelete(string $key, array $value)
    {
        $key = 'foo/bar.baz';
        $value = ['foo' => 'bar'];

        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->assertFalse($storage->has($key));

        $storage->set($key, $value);

        $this->assertTrue($storage->has($key));
        $this->assertEquals($value, $storage->get($key));

        $storage->delete($key);

        $this->assertFalse($storage->has($key));
    }

    /**
     * @dataProvider getValues
     */
    public function testGetLocked(string $key, array $value)
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $storage->set($key, $value);

        $this->assertEquals($value, $storage->getLocked($key));

        $dataPath = $this->rootDir.'/'.$key.'.config';
        $handle = fopen($dataPath, 'r+');

        $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB), 'Data file should be locked');

        $storage->releaseLock($key);

        $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB), 'Data file should be locked');

        flock($handle, LOCK_UN | LOCK_NB);
        fclose($handle);

        $this->expectException('RuntimeException');

        $storage->releaseLock($key);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testSetInvalidKeyThrows($key)
    {
        $storage = new DeferredImageStorageFilesystem($this->rootDir);

        $this->expectException('InvalidArgumentException');

        $storage->set($key, []);
    }

    public function getValues()
    {
        yield ['foo', ['foo' => 'bar']];
        yield ['foo/bar.baz', ['foo' => ['nested' => ['array', 0, false]]]];
        yield ['foo/bar/baz/nested/path.jpg', ['foo' => 'bar']];
        yield ['foo.config', ['foo' => 'bar']];
    }

    public function invalidKeys()
    {
        yield ['/foo'];
        yield ['foo/'];
        yield ['foo//bar'];
        yield ['../foo'];
    }
}
