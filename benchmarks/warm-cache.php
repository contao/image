<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Contao\Image\BackendRegistry;
use Contao\Image\FilesystemImageSource;
use Contao\Image\FilesystemSourceInformationStorage;
use Contao\Image\FilesystemSourceResolver;
use Contao\Image\FilesystemTargetContentStorage;
use Contao\Image\FilesystemTargetRecordStorage;
use Contao\Image\Image;
use Contao\Image\ImageLifecycle;
use Contao\Image\ImageResizer;
use Contao\Image\ImagineImageDecoderEncoder;
use Contao\Image\LocalStoredImageBackend;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGenerator;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Contao\Image\ResponsivePictureGenerator;
use Contao\Image\SourceInformationProvider;
use Contao\Image\SourceReference;
use Contao\Image\StreamImageInspector;
use Contao\Image\TargetCoordinator;
use Contao\Image\Tests\Fixture\ContentStorageDecorator;
use Contao\Image\Tests\Fixture\InformationStorageDecorator;
use Contao\Image\Tests\Fixture\RecordStorageDecorator;
use Contao\ImagineSvg\Imagine as SvgImagine;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;

$root = sys_get_temp_dir().'/contao-image-benchmark-'.bin2hex(random_bytes(8));
$filesystem = new Filesystem();
$filesystem->mkdir($root);
$iterations = (int) ($argv[1] ?? 200);
$measure = static function (callable $work) use ($iterations): array {
    $times = [];
    for ($i = 0; $i < $iterations; ++$i) {
        $start = hrtime(true);
        $work();
        $times[] = (hrtime(true) - $start) / 1000;
    }
    sort($times);
    return ['median_us' => round($times[(int) floor(count($times) / 2)], 2), 'p95_us' => round($times[(int) floor(count($times) * .95)], 2)];
};
$report = ['php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'iterations' => $iterations];
try {
    foreach (['jpg', 'svg'] as $format) {
        $path = $root.'/source.'.$format;
        if ('jpg' === $format) {
            (new Imagine())->create(new Box(600, 400))->save($path);
        } else {
            $filesystem->dumpFile($path, '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><rect width="600" height="400" fill="red"/></svg>');
        }
        $reference = new SourceReference('assets', basename($path));
        $source = new class($path, $reference) extends FilesystemImageSource {
            public int $reads = 0;
            public function openStream() { ++$this->reads; return parent::openStream(); }
        };
        $records = new RecordStorageDecorator(new FilesystemTargetRecordStorage($root.'/new'));
        $content = new ContentStorageDecorator(new FilesystemTargetContentStorage($root.'/new/content', '/images'));
        $backend = new LocalStoredImageBackend(new ImagineImageDecoderEncoder(new Imagine()), $content);
        $registry = new BackendRegistry([$backend]);
        $resolver = new FilesystemSourceResolver(['assets' => $root]);
        $storage = new InformationStorageDecorator(new FilesystemSourceInformationStorage($root.'/new'));
        $information = new SourceInformationProvider($storage, new StreamImageInspector());
        $lifecycle = new ImageLifecycle($records, $storage, $registry, $resolver);
        $targets = new TargetCoordinator($records, $registry, $resolver, $lifecycle);
        $resizer = new ImageResizer($information, $targets, $lifecycle, $backend, 'benchmark-secret');
        $generator = new ResponsivePictureGenerator($resizer, $information, $lifecycle);
        $config = (new PictureConfiguration())->withSize((new PictureConfigurationItem())->withResizeConfig((new ResizeConfiguration())->withWidth(120))->withDensities('1x,2x,3x'));
        $work = static fn () => $generator->generate($source, $config, new ProcessingOptions())->getImg();
        $work();
        $source->reads = 0; $content->counts = []; $records->counts = []; $storage->counts = [];
        $new = $measure($work);
        $new['source_content_opens'] = $source->reads;
        $new['target_content_operations'] = $content->counts;
        $new['information_operations_per_picture'] = array_map(static fn ($count) => $count / $iterations, $storage->counts);
        $new['record_operations_per_picture'] = array_map(static fn ($count) => $count / $iterations, $records->counts);
        if ($source->reads || $content->counts) { throw new RuntimeException('Warm content I/O regression'); }
        $report[$format]['new'] = $new;
        $report[$format]['source_shared_lock'] = $measure(static function () use ($records, $reference): void { $records->lockSource($reference)->release(); });
        if (class_exists(Image::class)) {
            $legacy = new PictureGenerator(new Resizer($root.'/legacy', 'benchmark-secret'));
            $driver = 'svg' === $format ? new SvgImagine() : new Imagine();
            $oldWork = static fn () => $legacy->generate(new Image($path, $driver), $config, new ResizeOptions())->getImg($root);
            $oldWork();
            $report[$format]['legacy'] = $measure($oldWork);
        }
    }
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
} finally {
    $filesystem->remove($root);
}
