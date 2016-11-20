Contao image library
====================

[![](https://img.shields.io/travis/contao/image/master.svg?style=flat-square)](https://travis-ci.org/contao/image/)
[![](https://img.shields.io/scrutinizer/g/contao/image/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/image/)
[![](https://img.shields.io/coveralls/contao/image/master.svg?style=flat-square)](https://coveralls.io/github/contao/image)
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

### Simple resize:

```php
$imagine = new \Imagine\Gd\Imagine();

$resizer = new Resizer('/path/to/cache/dir');

$image = new Image('/path/to/image.jpg', $imagine);

$config = (new ResizeConfiguration())
    ->setWidth(100)
    ->setHeight(100)
    ->setMode(ResizeConfiguration::MODE_CROP)
;

$options = (new ResizeOptions())
    ->setImagineOptions(['jpeg_quality' => 95])
    ->setBypassCache(true)
    ->setTargetPath('/custom/target/path.jpg')
;

$resizedImage = $resizer->resize($image, $config, $options);

$resizedImage->getPath(); // /custom/target/path.jpg
$resizedImage->getUrl('/custom/target'); // path.jpg
$resizedImage->getUrl('/custom/target', 'https://example.com/'); // https://example.com/path.jpg
```

### Responsive image:

```php
$imagine = new \Imagine\Gd\Imagine();

$resizer = new Resizer('/path/to/cache/dir');
$pictureGenerator = new PictureGenerator($resizer);

$image = new Image('/path/to/image.jpg', $imagine);

$config = (new PictureConfiguration())
    ->setSize((new PictureConfigurationItem())
        ->setResizeConfig((new ResizeConfiguration())
            ->setWidth(100)
            ->setHeight(100)
            ->setMode(ResizeConfiguration::MODE_CROP)
        )
        ->setDensities('1x, 2x')
        ->setSizes('100vw')
    )
    ->setSizeItems([
        (new PictureConfigurationItem())
            ->setResizeConfig((new ResizeConfiguration())
                ->setWidth(400)
                ->setHeight(200)
                ->setMode(ResizeConfiguration::MODE_CROP)
            )
            ->setDensities('1x, 2x')
            ->setSizes('100vw')
            ->setMedia('(min-width: 900px)')
    ])
;

$options = (new ResizeOptions());
$picture = $pictureGenerator->generate($image, $config, $options);

$picture->getImg('/path/to');
/* [
    'src' => 'cache/dir/4/image-de332f09.jpg',
    'width' => 100,
    'height' => 100,
    'srcset' => 'cache/dir/4/image-de332f09.jpg 100w, cache/dir/4/image-9e0829dd.jpg 200w',
    'sizes' => '100vw',
] */

$picture->getSources('/path/to', 'https://example.com/');
/* [
    [
        'src' => 'https://example.com/cache/dir/c/image-996db4cf.jpg',
        'width' => 400,
        'height' => 200,
        'srcset' => 'https://example.com/cache/dir/c/image-996db4cf.jpg 400w, https://example.com/cache/dir/2/image-457dc5e0.jpg 800w',
        'sizes' => '100vw',
        'media' => '(min-width: 900px)',
    ],
] */
```

### Image dimensions cache

If `ImageInterface::getDimensions()` gets called often in a project, it could
make sense to use a cache for the dimensions. Any cache that implements
[PSR-6][2] can be used, for example the Symfony cache component:

```php
$imagine = new \Imagine\Gd\Imagine();
$image = new Image('/path/to/image.jpg', $imagine);

$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter();
$image->setDimensionsCache($cache);

// Uses the $cache object to get the dimensions
$image->getDimensions();
```

[1]: https://contao.org
[2]: http://www.php-fig.org/psr/psr-6/
