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
        header('Content-Type: '.('jpg' === $result->getFormat() ? 'image/jpeg' : 'image/'.$result->getFormat()));
        try { fpassthru($stream); } finally { fclose($stream); }
    } else {
        header('Location: '.$result->getUrl(), true, 302);
    }
} catch (ExceptionInterface $exception) {
    http_response_code(409);
    echo 'Image unavailable';
}
