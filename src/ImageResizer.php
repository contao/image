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

use Contao\Image\Exception\InvalidArgumentException;
use Contao\Image\Internal\ImageExporter;
use Contao\Image\Internal\Json;

final class ImageResizer implements ImageResizerInterface
{
    public function __construct(private SourceInformationProvider $information, private TargetCoordinator $targets, private ImageLifecycle $lifecycle, private ImageBackendInterface $backend, private string $secret, private ?PublicSourceImageBackend $publicSources = null, private ImageExporter $exporter = new ImageExporter())
    {
        if ('' === $secret) {
            throw new InvalidArgumentException('Target hashing secret must not be empty');
        }
    }

    public function resize(ImageSourceInterface $source, ResizeConfiguration $configuration, ProcessingOptions $options): ImageResultInterface
    {
        if (null !== $options->getDestination()) {
            $this->exporter->validateDestination($options->getDestination(), $source instanceof FilesystemImageSource ? $source->getPath() : null);
        }
        $version = SourceVersion::capture($source);
        $lock = $this->lifecycle->acquire($version);

        try {
            $transformation = $this->transformation($source, clone $configuration, $options);
            $backend = $transformation->isCopy() && $source instanceof PublicImageSourceInterface && null !== $this->publicSources && null === $options->getDestination() ? $this->publicSources : $this->backend;
            $request = $this->request($source, $transformation, $backend);
            $version->assertMatches($source);
            $result = $this->targets->register($request, $backend, $source);
        } finally {
            $lock->release();
        }

        return null === $options->getDestination() ? $result : $this->exporter->export($result, $options->getDestination());
    }

    private function transformation(ImageSourceInterface $source, ResizeConfiguration $configuration, ProcessingOptions $options): ImageTransformation
    {
        $information = $this->information->get($source, SourceVersion::capture($source));
        $options = $options->withEncoding($options->getEncoding()->resolve($information->getFormat()));
        $dimensions = $information->getDimensions();
        $coordinates = (new TransformationCalculator())->calculate($configuration, $dimensions, $source->getImportantPart());
        $transformation = new ImageTransformation($information, $coordinates, $options);
        $sameFormat = $transformation->getOutputInformation()->getFormat() === $information->getFormat();
        $skip = $options->getSkipIfDimensionsMatch() && 1 === $dimensions->getOrientation() && $sameFormat;

        return $transformation->withCopy($dimensions->isUndefined() || ($skip && ($configuration->isEmpty() || (!$dimensions->isRelative() && $coordinates->isEqualTo($dimensions->getSize())))));
    }

    private function request(ImageSourceInterface $source, ImageTransformation $transformation, ImageBackendInterface $backend): TargetRequest
    {
        $version = SourceVersion::capture($source);
        $hash = hash_hmac('sha256', Json::encode([$version->toArray(), $transformation->toArray(), $backend->getIdentifier(), $backend->getCacheNamespace()]), $this->secret);
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($source->getFilename(), PATHINFO_FILENAME));
        $identity = new TargetIdentity($version->getPath().'/'.$hash.'/'.substr($name ?: 'image', 0, 80).'.'.$transformation->getOutputInformation()->getFormat());

        return new TargetRequest($identity, $version, $transformation);
    }
}
