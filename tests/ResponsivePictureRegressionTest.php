<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests;

use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ProcessingOptions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\SourceReference;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;

class ResponsivePictureRegressionTest extends AbstractImagePipelineTest
{
    /**
     * @dataProvider pictures
     */
    public function testLegacyPictureParity(array $case): void
    {
        [$width, $height, $format] = $case['source'];

        if ('svg' === $format) {
            (new Filesystem())->dumpFile($this->directory.'/source', \sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d"/>', $width, $height));
        } else {
            (new Imagine())->create(new Box($width, $height))->save($this->directory.'/source', ['format' => $format]);
        }
        $config = $case['configuration'];
        $pictureConfig = (new PictureConfiguration())->withFormats($config['formats'])->withSize($this->item($config['size']))->withSizeItems(array_map($this->item(...), $config['items']));
        $source = $this->resolver->resolve(new SourceReference('assets', 'source'));
        $picture = $this->factory->getPictureGenerator()->generate($source, $pictureConfig, (new ProcessingOptions())->withDeferred(true));
        $map = [];

        foreach ([$picture->getRawImg(), ...$picture->getRawSources()] as $attributes) {
            foreach ($attributes['srcset'] as [$result]) {
                $map[$result->getUrl()] = 'image-'.$result->getDimensions()->getSize()->getWidth().'.'.$result->getFormat();
            }
        }
        $actual = json_encode(['getImg' => $picture->getImg(), 'getSources' => $picture->getSources()], JSON_UNESCAPED_SLASHES);
        $this->assertSame($case['expected'], json_decode(strtr($actual, $map), true));
    }

    public static function pictures(): iterable
    {
        foreach (json_decode(file_get_contents(__DIR__.'/data/pictures.json'), true) as $name => $case) {
            yield $name => [$case];
        }
    }

    private function item(array $config): PictureConfigurationItem
    {
        return (new PictureConfigurationItem())->withResizeConfig((new ResizeConfiguration())->withWidth($config['width'])->withHeight($config['height']))->withDensities($config['densities'])->withSizes($config['sizes'])->withMedia($config['media']);
    }
}
