<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/RecordingCodec.php';

use Contao\Image\FilesystemImageConfiguration;
use Contao\Image\FilesystemImageFactory;
use Contao\Image\FilesystemSourceResolver;
use Contao\Image\ImagineImageDecoderEncoder;
use Imagine\Gd\Imagine;

$directory = __DIR__.'/var';
$codec = new RecordingCodec(new ImagineImageDecoderEncoder(new Imagine()));
$resolver = new FilesystemSourceResolver(['assets' => $directory]);
$factory = new FilesystemImageFactory(new FilesystemImageConfiguration($directory.'/cache', '/images', 'example-secret'), $resolver, $codec);
