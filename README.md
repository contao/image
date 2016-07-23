Contao image library
====================

[![](https://img.shields.io/travis/contao/image/master.svg?style=flat-square)](https://travis-ci.org/contao/image/)
[![](https://img.shields.io/scrutinizer/g/contao/image/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/contao/image/)
[![](https://img.shields.io/coveralls/contao/image/master.svg?style=flat-square)](https://coveralls.io/github/contao/image)

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
$filesystem = new \Symfony\Component\Filesystem\Filesystem();

$calculator = new ResizeCalculator();
$resizer = new Resizer($calculator, $filesystem, '/path/to/cache/dir');

$image = new Image($imagine, $filesystem, '/path/to/image.jpg');

$config = (new ResizeConfiguration())
	->setWidth(100)
	->setHeight(100)
	->setMode(ResizeConfiguration::MODE_CROP);

$options = (new ResizeOptions())
	->setImagineOptions(['jpeg_quality' => 95])
	->setBypassCache(true)
	->setTargetPath('/custom/target/path.jpg');

$resizedImage = $resizer->resize($image, $config, $options);

$resizedImage->getPath(); // /custom/target/path.jpg
$resizedImage->getUrl('/custom/target'); // path.jpg
```

### Responsive image:

```php
$imagine = new \Imagine\Gd\Imagine();
$filesystem = new \Symfony\Component\Filesystem\Filesystem();

$calculator = new ResizeCalculator();
$resizer = new Resizer($calculator, $filesystem, '/path/to/cache/dir');
$pictureGenerator = new PictureGenerator($resizer);

$image = new Image($imagine, $filesystem, '/path/to/image.jpg');

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
	]);

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

$picture->getSources('/path/to');
/* [
	[
		'src' => 'cache/dir/c/image-996db4cf.jpg',
		'width' => 400,
		'height' => 200,
		'srcset' => 'cache/dir/c/image-996db4cf.jpg 0w, cache/dir/2/image-457dc5e0.jpg 800w',
		'sizes' => '100vw',
		'media' => '(min-width: 900px)',
	],
] */
```

[1]: https://contao.org
