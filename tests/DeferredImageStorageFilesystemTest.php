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
}
