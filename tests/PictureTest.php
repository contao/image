<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Test;

use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;

/**
 * Tests the Picture class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $picture = $this->createPicture();

        $this->assertInstanceOf('Contao\Image\Picture', $picture);
        $this->assertInstanceOf('Contao\Image\PictureInterface', $picture);
    }

    /**
     * Tests the getImg() method.
     */
    public function testGetImg()
    {
        $picture = $this->createPicture(null, '/path/to/a/filename with special&<>"\'chars.jpeg');

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getImg()['src']);

        $this->assertEquals(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/')['src']
        );

        $this->assertEquals(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path')['src'])
        ;

        $this->assertEquals(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path/to')['src'])
        ;

        $this->assertEquals(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg',
            $picture->getImg('/path/to/a')['src'])
        ;

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getImg()['srcset'][0][0]);

        $this->assertEquals(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/')['srcset']
        );

        $this->assertEquals(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path')['srcset']
        );

        $this->assertEquals(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path/to')['srcset']
        );

        $this->assertEquals(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getImg('/path/to/a')['srcset']
        );

        $this->assertEquals('custom attribute', $picture->getImg()['data-custom']);
        $this->assertEquals('custom attribute', $picture->getImg('/')['data-custom']);

        $this->setExpectedException('InvalidArgumentException');

        $picture->getImg('/path/t');
    }

    /**
     * Tests the getSources() method.
     */
    public function testGetSources()
    {
        $picture = $this->createPicture(null, '/path/to/a/filename with special&<>"\'chars.jpeg');

        $this->assertInstanceOf('Contao\Image\ImageInterface', $picture->getSources()[0]['srcset'][0][0]);

        $this->assertEquals(
            'path/to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/')[0]['srcset']
        );

        $this->assertEquals(
            'to/a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path')[0]['srcset']
        );

        $this->assertEquals(
            'a/filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path/to')[0]['srcset']
        );

        $this->assertEquals(
            'filename%20with%20special%26%3C%3E%22%27chars.jpeg 1x',
            $picture->getSources('/path/to/a')[0]['srcset']
        );

        $this->assertEquals('custom attribute', $picture->getSources()[0]['data-custom']);
        $this->assertEquals('custom attribute', $picture->getSources('/')[0]['data-custom']);

        $this->setExpectedException('InvalidArgumentException');

        $picture->getSources('/path/t');
    }

    /**
     * Tests the constructor with a missing src attribute.
     */
    public function testMissingSrc()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing src attribute');

        new Picture(['srcset' => []], []);
    }

    /**
     * Tests the constructor with an invalid src attribute.
     */
    public function testInvalidSrc()
    {
        $this->setExpectedException('InvalidArgumentException', 'Src must be of type ImageInterface');

        new Picture(['src' => new \stdClass(), 'srcset' => []], []);
    }

    /**
     * Tests the constructor with a missing srcset attribute.
     */
    public function testMissingSrcset()
    {
        $image = $this->getMock('Contao\Image\ImageInterface');
        $this->setExpectedException('InvalidArgumentException', 'Missing srcset attribute');

        new Picture(['src' => $image], []);
    }

    /**
     * Tests the constructor with an invalid srcset attribute.
     */
    public function testInvalidSrcset()
    {
        $image = $this->getMock('Contao\Image\ImageInterface');
        $this->setExpectedException('InvalidArgumentException', 'Srcsets must be of type ImageInterface');

        new Picture(['src' => $image, 'srcset' => [[$image, '1x'], [new \stdClass(), '2x']]], []);
    }

    /**
     * Creates a picture instance helper.
     *
     * @param ImageInterface $image
     * @param string         $path
     *
     * @return Picture
     */
    private function createPicture($image = null, $path = 'dummy.jpg')
    {
        if (null === $image) {
            $imagine = $this->getMock('Imagine\Image\ImagineInterface');
            $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
            $filesystem->method('exists')->willReturn(true);
            $image = new Image($path, $imagine, $filesystem);
        }

        return new Picture(
            ['src' => $image, 'srcset' => [[$image, '1x']], 'data-custom' => 'custom attribute'],
            [['srcset' => [[$image, '1x']], 'data-custom' => 'custom attribute']]
        );
    }
}
