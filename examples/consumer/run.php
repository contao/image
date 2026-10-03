<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use Contao\Image\EncodingOptions;
use Contao\Image\LocalImageResultInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ProcessingOptions;
use Contao\Image\ReadableImageResultInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;

(new Filesystem())->remove($directory);
(new Filesystem())->mkdir($directory);
(new Imagine())->create(new Box(300, 200))->save($directory.'/photo.jpg');
$source = $resolver->resolve(new SourceReference('assets', 'photo.jpg'));
$options = (new ProcessingOptions())->withEncoding((new EncodingOptions())->withQuality(80)->withAdapterOptions('imagine', ['jpeg_sampling_factors' => [2, 1, 1]]));
$immediate = $factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(150), $options);
if (!$immediate instanceof LocalImageResultInterface || !is_file($immediate->getPath())) {
    throw new RuntimeException('Immediate processing failed');
}
$picture = $factory->getPictureGenerator()->generate($source, (new PictureConfiguration())->withSize((new PictureConfigurationItem())->withResizeConfig((new ResizeConfiguration())->withWidth(60))->withDensities('1x,2x')), $options->withDeferred(true));
foreach ($factory->getRecords()->listPending() as $record) {
    $factory->getDeferredProcessor()->executeDeferred($record->getIdentity(), false);
}
$deferred = $factory->getResizer()->resize($source, (new ResizeConfiguration())->withWidth(90), $options->withDeferred(true));
$response = $factory->getDeferredProcessor()->executeDeferred($deferred->getIdentity());
if (!$response instanceof ReadableImageResultInterface) {
    throw new RuntimeException('Expected readable deferred response');
}
$body = $response->openStream();
$bytes = stream_get_contents($body);
fclose($body);
$child = $factory->getResizer()->resize($response->asSource(), (new ResizeConfiguration())->withWidth(30), $options);
if (30 !== $child->getDimensions()->getSize()->getWidth() || '' === $bytes || $codec->transformations < 5) {
    throw new RuntimeException('Consumer verification failed');
}
echo json_encode(['image' => $immediate->getUrl(), 'picture' => $picture->getImg(), 'child' => $child->getUrl(), 'transformations' => $codec->transformations], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
