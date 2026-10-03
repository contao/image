<?php

declare(strict_types=1);

use Contao\Image\ImageBackendInterface;
use Contao\Image\ImageSourceInterface;
use Contao\Image\PreparedTarget;
use Contao\Image\TargetDescription;
use Contao\Image\TargetRequest;

final class UrlOnlyBackend implements ImageBackendInterface
{
    public function __construct(private string $baseUrl)
    {
    }

    public function getIdentifier(): string { return 'example-url-service'; }
    public function getCacheNamespace(): string { return 'example-transform-v1'; }

    public function prepare(TargetRequest $request): PreparedTarget
    {
        $data = ['source' => $request->getSource()->toArray(), 'transformation' => $request->getTransformation()->toArray()];
        return new PreparedTarget(new TargetDescription($this->getIdentifier(), $request->getIdentity()->getPath(), $data), $request->getTransformation(), true);
    }

    public function execute(ImageSourceInterface $source, PreparedTarget $target): TargetDescription
    {
        return $target->getDescription();
    }

    public function getUrl(TargetDescription $description): string
    {
        // An actual service must resolve the exact version in this description
        return rtrim($this->baseUrl, '/').'/transform?'.http_build_query($description->getData());
    }

    public function delete(TargetDescription $description): void
    {
        // This stateless example has no generated content to delete
    }
}
