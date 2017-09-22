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
use PHPUnit\Framework\TestCase;

class ResizeOptionsTest extends TestCase
{
    public function testInstantiation()
    {
        $options = new ResizeOptions();

        $this->assertInstanceOf('Contao\Image\ResizeOptions', $options);
        $this->assertInstanceOf('Contao\Image\ResizeOptionsInterface', $options);
    }

    public function testSetImagineOptions()
    {
        $options = new ResizeOptions();

        $this->assertSame([], $options->getImagineOptions());
        $this->assertSame($options, $options->setImagineOptions(['jpeg_quality' => 95]));
        $this->assertSame(['jpeg_quality' => 95], $options->getImagineOptions());
    }

    public function testSetTargetPath()
    {
        $options = new ResizeOptions();

        $this->assertSame(null, $options->getTargetPath());
        $this->assertSame($options, $options->setTargetPath('/target/path'));
        $this->assertSame('/target/path', $options->getTargetPath());

        $options->setTargetPath(null);
        $this->assertSame(null, $options->getTargetPath());

        $this->expectException('InvalidArgumentException');

        $options->setTargetPath('invalid/relative/path');
    }

    public function testSetBypassCache()
    {
        $options = new ResizeOptions();

        $this->assertSame(false, $options->getBypassCache());
        $this->assertSame($options, $options->setBypassCache(1));
        $this->assertSame(true, $options->getBypassCache());
        $this->assertInternalType('bool', $options->getBypassCache());
    }
}
