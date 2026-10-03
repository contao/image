Contao image library
====================

[![](https://img.shields.io/github/actions/workflow/status/contao/image/ci.yml?branch=2.x&style=flat-square)](https://github.com/contao/image/actions?query=branch%3A2.x)
[![](https://img.shields.io/codecov/c/github/contao/image/2.x.svg?style=flat-square)](https://codecov.io/gh/contao/image)
[![](https://img.shields.io/packagist/v/contao/image.svg?style=flat-square)](https://packagist.org/packages/contao/image)
[![](https://img.shields.io/packagist/dt/contao/image.svg?style=flat-square)](https://packagist.org/packages/contao/image)

This library provides methods to resize images based on resize configurations
and generates responsive images to be used with `<picture>` and `srcset`. It is
used in [Contao][1] to handle on-the-fly resizing of images.

Installation
------------

```sh
php composer.phar require contao/image
```

Usage
-----

PHP 8.4 or later is required. Clear the complete image cache when migrating to 2.0.

```php
$resolver = new Contao\Image\FilesystemSourceResolver(['uploads' => '/path/to/uploads']);
$factory = new Contao\Image\FilesystemImageFactory(
    new Contao\Image\FilesystemImageConfiguration('/path/to/cache', '/images', $secret),
    $resolver,
    new Contao\Image\ImagineImageDecoderEncoder(new Imagine\Gd\Imagine())
);
$source = $resolver->resolve(new Contao\Image\SourceReference('uploads', 'photo.jpg'));
$result = $factory->getResizer()->resize(
    $source,
    (new Contao\Image\ResizeConfiguration())->withWidth(640),
    new Contao\Image\ProcessingOptions()
);
echo $result->getUrl();
```

See the [API guide](docs/image-api.md), [migration guide](docs/migration-2.0.md),
[storage contract](docs/storage.md), [format matrix](docs/formats.md) and
[validation results](docs/validation.md). A runnable consumer supports both 1.3 and 2.0.

[1]: https://contao.org
