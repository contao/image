<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class PictureTest extends TestCase
{
    public function testInstantiation()
    {
        $picture = $this->createPicture();

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    public function testGetImg()
    {
        $picture = $this->createPicture(null, '/path/to/a/filename with special&<>"\'chars.jpeg');

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getImg()['src']);

        $this->assertSame(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/')['src']
        );

        $this->assertSame(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path')['src']
        );

        $this->assertSame(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path/to')['src']
        );

        $this->assertSame(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path/to/a')['src']
        );

        $this->assertSame(
            'https://example.com/images/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path/to', 'https://example.com/images/')['src']
        );

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getImg()['srcset'][0][0]);

        $this->assertSame(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/')['srcset']
        );

        $this->assertSame(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path')['srcset']
        );

        $this->assertSame(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path/to')['srcset']
        );

        $this->assertSame(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path/to/a')['srcset']
        );

        $this->assertSame(
            'https://example.com/images/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path/to', 'https://example.com/images/')['srcset']
        );

        $this->assertSame('custom attribute', $picture->getImg()['data-custom']);
        $this->assertSame('custom attribute', $picture->getImg('/')['data-custom']);

        $this->expectException('InvalidArgumentException');

        $picture->getImg(null, 'https://example.com/images/');
    }

    public function testGetSources()
    {
        $picture = $this->createPicture(null, '/path/to/a/filename with special&<>"\'chars.jpeg');

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getSources()[0]['srcset'][0][0]);

        $this->assertSame(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/')[0]['srcset']
        );

        $this->assertSame(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path')[0]['srcset']
        );

        $this->assertSame(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path/to')[0]['srcset']
        );

        $this->assertSame(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path/to/a')[0]['srcset']
        );

        $this->assertSame(
            'https://example.com/images/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path/to', 'https://example.com/images/')[0]['srcset']
        );

        $this->assertSame('custom attribute', $picture->getSources()[0]['data-custom']);
        $this->assertSame('custom attribute', $picture->getSources('/')[0]['data-custom']);

        $this->expectException('InvalidArgumentException');

        $picture->getSources(null, 'https://example.com/images/');
    }

    public function testMissingSrc()
    {
        $this->expectException('InvalidArgumentException');

        new Picture(['srcset' => []], []);
    }

    public function testInvalidSrc()
    {
        $this->expectException('InvalidArgumentException');

        new Picture(['src' => new \stdClass(), 'srcset' => []], []);
    }

    public function testMissingSrcset()
    {
        $image = $this->createMock(ImageInterface::class);
        $this->expectException('InvalidArgumentException');

        new Picture(['src' => $image], []);
    }

    public function testInvalidSrcset()
    {
        $image = $this->createMock(ImageInterface::class);
        $this->expectException('InvalidArgumentException');

        new Picture(['src' => $image, 'srcset' => [[$image, '1x'], [new \stdClass(), '2x']]], []);
    }

    /**
     * Returns a picture.
     *
     * @param ImageInterface $image
     * @param string         $path
     *
     * @return Picture
     */
    private function createPicture($image = null, $path = 'dummy.jpg')
    {
        if (null === $image) {
            $imagine = $this->createMock(ImagineInterface::class);

            $filesystem = $this->createMock(Filesystem::class);
            $filesystem
                ->method('exists')
                ->willReturn(true)
            ;

            $image = new Image($path, $imagine, $filesystem);
        }

        return new Picture(
            ['src' => $image, 'srcset' => [[$image, '1x']], 'data-custom' => 'custom attribute'],
            [['srcset' => [[$image, '1x']], 'data-custom' => 'custom attribute']]
        );
    }
}
