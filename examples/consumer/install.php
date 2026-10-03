<?php

declare(strict_types=1);

$version = $argv[1] ?? '1.3.99';
if (!preg_match('/^(1\.3|2\.0)\.[0-9]+$/D', $version)) {
    throw new InvalidArgumentException('Use a local 1.3 or 2.0 version override');
}
$config = json_decode(file_get_contents(__DIR__.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$config['config']['platform']['php'] = PHP_VERSION;
$config['repositories'] = [['type' => 'path', 'url' => dirname(__DIR__, 2), 'options' => ['symlink' => true, 'versions' => ['contao/image' => $version]]]];
file_put_contents(__DIR__.'/.composer-local.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
putenv('COMPOSER='.__DIR__.'/.composer-local.json');
$process = proc_open(['composer', 'update', '--no-interaction', '--no-progress'], [STDIN, STDOUT, STDERR], $pipes, __DIR__);
exit(proc_close($process));
