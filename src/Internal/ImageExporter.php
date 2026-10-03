<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Internal;

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\ExportedImageResult;
use Contao\Image\FilesystemTargetContentStorage;
use Contao\Image\ImageResultInterface;
use Contao\Image\LocalImageResultInterface;
use Contao\Image\ReadableImageResultInterface;
use Symfony\Component\Filesystem\Path;

final class ImageExporter
{
    public function __construct(private array $managedDirectories = [])
    {
    }

    public function validateDestination(string $destination, ?string $sourcePath = null): void
    {
        $destination = Path::canonicalize($destination);

        if (null !== $sourcePath && $destination === Path::canonicalize($sourcePath)) {
            throw new InvalidArgumentException('Export destination cannot overwrite its source');
        }

        foreach ($this->managedDirectories as $directory) {
            if (Path::isBasePath(Path::canonicalize($directory), $destination)) {
                throw new InvalidArgumentException('Export destination must be outside managed storage');
            }
        }
    }

    public function export(ImageResultInterface $result, string $destination): ImageResultInterface
    {
        if (!$result instanceof ReadableImageResultInterface) {
            throw new InvalidArgumentException('Backend cannot export unreadable results');
        }

        if ($result instanceof LocalImageResultInterface && $destination === $result->getPath()) {
            throw new InvalidArgumentException('Export destination cannot overwrite a managed target');
        }
        $destination = Path::canonicalize($destination);
        $this->validateDestination($destination);
        $input = $result->openStream();
        $lock = null;

        try {
            $lock = FilesystemLock::acquire(sys_get_temp_dir().'/contao-image-export-locks/'.hash('sha256', $destination).'.lock');
            (new FilesystemTargetContentStorage(\dirname($destination), ''))->publish(basename($destination), $input);
        } finally {
            fclose($input);
            $lock?->release();
        }

        return new ExportedImageResult($result, $destination);
    }
}
