<?php

declare(strict_types=1);

use Contao\Image\ImageDecoderEncoderInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\ImageTransformation;

final class RecordingCodec implements ImageDecoderEncoderInterface
{
    public int $transformations = 0;

    public function __construct(private ImageDecoderEncoderInterface $inner)
    {
    }

    public function transform(ImageSourceInterface $source, ImageTransformation $transformation)
    {
        ++$this->transformations;

        return $this->inner->transform($source, $transformation);
    }
}
