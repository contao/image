<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use Contao\Image\Exception\ExceptionInterface;
use Contao\Image\ReadableImageResultInterface;
use Contao\Image\TargetIdentity;

try {
    $result = $factory->getDeferredProcessor()->executeDeferred(new TargetIdentity($_GET['target'] ?? ''));
    if (null === $result) {
        http_response_code(404);
    } elseif ($result instanceof ReadableImageResultInterface) {
        $stream = $result->openStream();
        $format = $result->getFormat();
        $mime = match ($format) { 'jpg' => 'image/jpeg', 'svg', 'svgz' => 'image/svg+xml', default => 'image/'.$format };
        header('Content-Type: '.$mime);
        if ('svgz' === $format) { header('Content-Encoding: gzip'); }
        try { fpassthru($stream); } finally { fclose($stream); }
    } else {
        header('Location: '.$result->getUrl(), true, 302);
    }
} catch (ExceptionInterface $exception) {
    http_response_code(409);
    echo 'Image unavailable';
}
