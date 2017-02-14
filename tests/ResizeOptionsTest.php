<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Image\Tests;

use Contao\Image\ResizeOptions;

/**
 * Tests the ResizeOptions class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ResizeOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $options = new ResizeOptions();

        $this->assertInstanceOf('Contao\Image\ResizeOptions', $options);
        $this->assertInstanceOf('Contao\Image\ResizeOptionsInterface', $options);
    }

    /**
     * Tests the setImagineOptions() method.
     */
    public function testSetImagineOptions()
    {
        $options = new ResizeOptions();

        $this->assertEquals([], $options->getImagineOptions());
        $this->assertSame($options, $options->setImagineOptions(['jpeg_quality' => 95]));
        $this->assertEquals(['jpeg_quality' => 95], $options->getImagineOptions());
    }

    /**
     * Tests the setTargetPath() method.
     */
    public function testSetTargetPath()
    {
        $options = new ResizeOptions();

        $this->assertEquals(null, $options->getTargetPath());
        $this->assertSame($options, $options->setTargetPath('/target/path'));
        $this->assertEquals('/target/path', $options->getTargetPath());

        $options->setTargetPath(null);
        $this->assertEquals(null, $options->getTargetPath());

        $this->setExpectedException('InvalidArgumentException');

        $options->setTargetPath('invalid/relative/path');
    }

    /**
     * Tests the setBypassCache() method.
     */
    public function testSetBypassCache()
    {
        $options = new ResizeOptions();

        $this->assertEquals(false, $options->getBypassCache());
        $this->assertSame($options, $options->setBypassCache(1));
        $this->assertEquals(true, $options->getBypassCache());
        $this->assertInternalType('bool', $options->getBypassCache());
    }
}
