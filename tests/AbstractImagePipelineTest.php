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

use Contao\Image\FilesystemImageConfiguration;
use Contao\Image\FilesystemImageFactory;
use Contao\Image\FilesystemSourceResolver;
use Contao\Image\ImageSourceInterface;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\SourceReference;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractImagePipelineTest extends TestCase
{
    protected string $directory;

    protected FilesystemSourceResolver $resolver;

    protected FilesystemImageFactory $factory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/contao-image-test-'.bin2hex(random_bytes(8));
        (new Filesystem())->mkdir($this->directory);
        $this->resolver = new FilesystemSourceResolver(['assets' => $this->directory]);
        $this->factory = new FilesystemImageFactory(new FilesystemImageConfiguration($this->directory.'/cache', '/images', 'test-secret'), $this->resolver, new ImagineImageDecoderEncoder(new Imagine()));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    protected function source(string $format = 'jpg', string $name = 'source'): ImageSourceInterface
    {
        (new Imagine())->create(new Box(120, 80))->save($this->directory.'/'.$name, ['format' => $format]);

        return $this->resolver->resolve(new SourceReference('assets', $name));
    }
}
