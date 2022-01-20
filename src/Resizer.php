<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Palette\RGB;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Resizer implements ResizerInterface
{
    /**
     * @var Filesystem
     *
     * @internal
     */
    protected $filesystem;

    /**
     * @var string
     *
     * @internal
     */
    protected $cacheDir;

    /**
     * @var ResizeCalculator
     */
    private $calculator;

    /**
     * @var string|null
     */
    private $secret;

    /**
     * @param string                $cacheDir
     * @param string                $secret
     * @param ResizeCalculator|null $calculator
     * @param Filesystem|null       $filesystem
     */
    public function __construct(string $cacheDir /*, string $secret, ResizeCalculator $calculator = null, Filesystem $filesystem = null*/)
    {
        if (\func_num_args() > 1 && \is_string(func_get_arg(1))) {
            $secret = func_get_arg(1);
            $calculator = \func_num_args() > 2 ? func_get_arg(2) : null;
            $filesystem = \func_num_args() > 3 ? func_get_arg(3) : null;
        } else {
            trigger_deprecation('contao/image', '1.2', 'Not passing a secret to "%s()" has been deprecated and will no longer work in version 2.0.', __METHOD__);
            $secret = null;
            $calculator = \func_num_args() > 1 ? func_get_arg(1) : null;
            $filesystem = \func_num_args() > 2 ? func_get_arg(2) : null;
        }

        if (null === $calculator) {
            $calculator = new ResizeCalculator();
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (!$calculator instanceof ResizeCalculator) {
            $type = \is_object($calculator) ? \get_class($calculator) : \gettype($calculator);

            throw new \TypeError(sprintf('%s(): Argument #3 ($calculator) must be of type ResizeCalculator|null, %s given', __METHOD__, $type));
        }

        if (!$filesystem instanceof Filesystem) {
            $type = \is_object($filesystem) ? \get_class($filesystem) : \gettype($filesystem);

            throw new \TypeError(sprintf('%s(): Argument #4 ($filesystem) must be of type ResizeCalculator|null, %s given', __METHOD__, $type));
        }

        if ('' === $secret) {
            throw new \InvalidArgumentException('$secret must not be empty');
        }

        $this->cacheDir = $cacheDir;
        $this->calculator = $calculator;
        $this->filesystem = $filesystem;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        if (
            $image->getDimensions()->isUndefined()
            || ($config->isEmpty() && $this->canSkipResize($image, $options))
        ) {
            $image = $this->createImage($image, $image->getPath());
        } else {
            $image = $this->processResize($image, $config, $options);
        }

        if (null !== $options->getTargetPath()) {
            $this->filesystem->copy($image->getPath(), $options->getTargetPath(), true);
            $image = $this->createImage($image, $options->getTargetPath());
        }

        return $image;
    }

    /**
     * Executes the resize operation via Imagine.
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinates $coordinates, string $path, ResizeOptions $options): ImageInterface
    {
        $dir = \dirname($path);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $imagineOptions = $options->getImagineOptions();
        $imagineImage = $image->getImagine()->open($image->getPath());

        if (ImageDimensions::ORIENTATION_NORMAL !== $image->getDimensions()->getOrientation()) {
            (new Autorotate())->apply($imagineImage);
        }

        $imagineImage
            ->resize($coordinates->getSize())
            ->crop($coordinates->getCropStart(), $coordinates->getCropSize())
            ->usePalette(new RGB())
            ->strip()
        ;

        if (isset($imagineOptions['interlace'])) {
            try {
                $imagineImage->interlace($imagineOptions['interlace']);
            } catch (ImagineRuntimeException $e) {
                // Ignore failed interlacing
            }
        }

        if (!isset($imagineOptions['format'])) {
            $imagineOptions['format'] = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        // Fix bug with undefined index notice in Imagine
        if ('webp' === $imagineOptions['format'] && !isset($imagineOptions['webp_quality'])) {
            $imagineOptions['webp_quality'] = 80;
        }

        // Atomic write operation
        $tmpPath = $this->filesystem->tempnam($dir, 'img');
        $this->filesystem->chmod($tmpPath, 0666, umask());
        $imagineImage->save($tmpPath, $imagineOptions);
        $this->filesystem->rename($tmpPath, $path, true);

        return $this->createImage($image, $path);
    }

    /**
     * Creates a new image instance for the specified path.
     *
     * @internal Do not call this method in your code; it will be made private in a future version
     */
    protected function createImage(ImageInterface $image, string $path): ImageInterface
    {
        return new Image($path, $image->getImagine(), $this->filesystem);
    }

    /**
     * Processes the resize and executes it if not already cached.
     *
     * @internal
     */
    protected function processResize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $coordinates = $this->calculator->calculate($config, $image->getDimensions(), $image->getImportantPart());

        // Skip resizing if it would have no effect
        if (
            $this->canSkipResize($image, $options)
            && !$image->getDimensions()->isRelative()
            && $coordinates->isEqualTo($image->getDimensions()->getSize())
        ) {
            return $this->createImage($image, $image->getPath());
        }

        $cachePath = Path::join($this->cacheDir, $this->createCachePath($image->getPath(), $coordinates, $options, false));

        if (!$options->getBypassCache()) {
            if ($this->filesystem->exists($cachePath)) {
                return $this->createImage($image, $cachePath);
            }

            $legacyCachePath = Path::join($this->cacheDir, $this->createCachePath($image->getPath(), $coordinates, $options, true));

            if ($this->filesystem->exists($legacyCachePath)) {
                trigger_deprecation('contao/image', '1.2', 'Reusing old cached images like "%s" from version 1.1 has been deprecated and will no longer work in version 2.0. Clear the image cache directory "%s" and regenerate all images to get rid of this message.', $legacyCachePath, $this->cacheDir);

                return $this->createImage($image, $legacyCachePath);
            }
        }

        return $this->executeResize($image, $coordinates, $cachePath, $options);
    }

    private function canSkipResize(ImageInterface $image, ResizeOptions $options): bool
    {
        if (!$options->getSkipIfDimensionsMatch()) {
            return false;
        }

        if (ImageDimensions::ORIENTATION_NORMAL !== $image->getDimensions()->getOrientation()) {
            return false;
        }

        if (
            isset($options->getImagineOptions()['format'])
            && $options->getImagineOptions()['format'] !== strtolower(pathinfo($image->getPath(), PATHINFO_EXTENSION))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns the relative target cache path.
     */
    private function createCachePath(string $path, ResizeCoordinates $coordinates, ResizeOptions $options, bool $useLegacyHash): string
    {
        $imagineOptions = $options->getImagineOptions();
        ksort($imagineOptions);

        $hashData = array_merge(
            [
                Path::makeRelative($path, $this->cacheDir),
                filemtime($path),
                $coordinates->getHash(),
            ],
            array_keys($imagineOptions),
            array_map(
                static function ($value) {
                    return \is_array($value) ? implode(',', $value) : $value;
                },
                array_values($imagineOptions)
            )
        );

        if ($useLegacyHash || null === $this->secret) {
            $hash = substr(md5(implode('|', $hashData)), 0, 9);
        } else {
            $hash = hash_hmac('sha256', implode('|', $hashData), $this->secret, true);
            $hash = substr($this->encodeBase32($hash), 0, 16);
        }

        $pathinfo = pathinfo($path);
        $extension = $options->getImagineOptions()['format'] ?? strtolower($pathinfo['extension']);

        return Path::join($hash[0], $pathinfo['filename'].'-'.substr($hash, 1).'.'.$extension);
    }

    /**
     * Encode a string with Crockford’s Base32 in lowercase
     * (0123456789abcdefghjkmnpqrstvwxyz).
     */
    private function encodeBase32(string $bytes): string
    {
        $result = [];

        foreach (str_split($bytes, 5) as $chunk) {
            $result[] = substr(
                str_pad(
                    strtr(
                        base_convert(bin2hex(str_pad($chunk, 5, "\0")), 16, 32),
                        'ijklmnopqrstuv',
                        'jkmnpqrstvwxyz' // Crockford's Base32
                    ),
                    8,
                    '0',
                    STR_PAD_LEFT
                ),
                0,
                (int) ceil(\strlen($chunk) * 8 / 5)
            );
        }

        return implode('', $result);
    }
}
