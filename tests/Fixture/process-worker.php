<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Contao\Image\Exception\ImageDecodingException;
use Contao\Image\FilesystemSourceInformationStorage;
use Contao\Image\FilesystemSourceResolver;
use Contao\Image\FilesystemTargetContentStorage;
use Contao\Image\FilesystemTargetRecordStorage;
use Contao\Image\ImageDecoderEncoderInterface;
use Contao\Image\ImageInformation;
use Contao\Image\ImageInspectorInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\ImageTransformation;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalStoredImageBackend;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceInformationProvider;
use Contao\Image\SourceInformationStorageInterface;
use Contao\Image\SourceReference;
use Contao\Image\SourceVersion;
use Contao\Image\StorageLockInterface;
use Contao\Image\StreamImageInspector;
use Contao\Image\TargetIdentity;
use Contao\Image\Tests\Fixture\ContentStorageDecorator;
use Contao\Image\Tests\Fixture\Pipeline;
use Contao\Image\Tests\Fixture\RecordStorageDecorator;
use Imagine\Gd\Imagine;
use Symfony\Component\Filesystem\Filesystem;

[, $mode, $directory, $identity] = $argv;
$resolver = new FilesystemSourceResolver(['assets' => $directory]);
$source = $resolver->resolve(new SourceReference('assets', 'source'));
fwrite(STDOUT, "ready\n");

if (str_starts_with($mode, 'inspection')) {
    $storage = new class(new FilesystemSourceInformationStorage($directory.'/race')) implements SourceInformationStorageInterface {
        public function __construct(private SourceInformationStorageInterface $inner)
        {
        }

        public function get(SourceVersion $source): ?ImageInformation
        {
            return $this->inner->get($source);
        }

        public function put(SourceVersion $source, ImageInformation $information): void
        {
            $this->inner->put($source, $information);
        }

        public function delete(SourceVersion $source): void
        {
            $this->inner->delete($source);
        }

        public function lock(SourceVersion $source): StorageLockInterface
        {
            fwrite(STDOUT, "locking\n");

            return $this->inner->lock($source);
        }
    };
    $inspector = new class($directory, $mode) implements ImageInspectorInterface {
        public function __construct(private string $directory, private string $mode)
        {
        }

        public function inspect(ImageSourceInterface $source): ImageInformation
        {
            fwrite(STDOUT, "inspect\n");
            fgets(STDIN);

            if ('inspection-fail' === $this->mode) {
                throw new ImageDecodingException('Injected inspection failure');
            }
            (new Filesystem())->appendToFile($this->directory.'/inspections', "inspection\n");

            return (new StreamImageInspector())->inspect($source);
        }
    };

    try {
        (new SourceInformationProvider($storage, $inspector))->get($source, SourceVersion::capture($source));
        fwrite(STDOUT, "done\n");
    } catch (ImageDecodingException) {
        fwrite(STDOUT, "failed\n");
    }
    exit(0);
}

$records = new RecordStorageDecorator(new FilesystemTargetRecordStorage($directory.'/race'));
$records->beforeTargetLock = static function (): void { fwrite(STDOUT, "target-lock\n"); };
$codec = new class($directory) implements ImageDecoderEncoderInterface {
    public function __construct(private string $directory)
    {
    }

    public function transform(ImageSourceInterface $source, ImageTransformation $transformation)
    {
        fwrite(STDOUT, "transform\n");
        fgets(STDIN);
        (new Filesystem())->appendToFile($this->directory.'/transformations', "transform\n");

        return (new ImagineImageDecoderEncoder(new Imagine()))->transform($source, $transformation);
    }
};
$storage = new ContentStorageDecorator(new FilesystemTargetContentStorage($directory.'/content', '/images'));

if ('prune' === $mode) {
    $storage->beforeDelete = static function (): void { fwrite(STDOUT, "delete\n"); fgets(STDIN); };
}

if ('regenerate' === $mode) {
    $records->beforeTargetLock = null;
    $records->beforeSourceLock = static function () use ($records): void { fwrite(STDOUT, "source-lock\n"); $records->beforeSourceLock = null; };
}
$backend = new LocalStoredImageBackend($codec, $storage);
$pipeline = new Pipeline($directory.'/race', $resolver, $backend, null, $records);

if ('prune' === $mode) {
    $pipeline->lifecycle->prune(new DateInterval('PT0S'));
} elseif (in_array($mode, ['regenerate', 'register'], true)) {
    $pipeline->resizer->resize($source, (new ResizeConfiguration())->withWidth(60), new ProcessingOptions());
} else {
    $pipeline->targets->executeDeferred(new TargetIdentity($identity));
}
fwrite(STDOUT, "done\n");
