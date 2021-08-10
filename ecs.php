<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\PhpUnit\PhpUnitExpectationFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__.'/tools/ecs/vendor/contao/easy-coding-standard/config/set/contao.php');

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SKIP, [
        PhpUnitExpectationFixer::class => [
            'tests/ImportantPartTest.php',
        ],
    ]);

    $parameters->set(Option::LINE_ENDING, "\n");
    $parameters->set(Option::CACHE_DIRECTORY, sys_get_temp_dir().'/ecs_default_cache');
};
