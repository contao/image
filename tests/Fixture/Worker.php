<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image\Tests\Fixture;

use PHPUnit\Framework\Assert;

final class Worker
{
    private $process;
    private array $pipes = [];

    public function __construct(string $mode, string $directory, string $identity = '')
    {
        // Direct pipes provide deterministic worker barriers for these contract tests
        // @phpstan-ignore-next-line
        $this->process = proc_open([PHP_BINARY, __DIR__.'/process-worker.php', $mode, $directory, $identity], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $this->pipes);

        foreach ([1, 2] as $index) {
            stream_set_timeout($this->pipes[$index], 15);
        }
    }

    public function __destruct()
    {
        if (\is_resource($this->process)) {
            $this->kill();
        }
    }

    public function expect(string $message): void
    {
        $line = fgets($this->pipes[1]);
        Assert::assertSame($message, false === $line ? '' : trim($line), 'Worker did not reach its synchronization barrier');
    }

    public function release(): void
    {
        fwrite($this->pipes[0], "continue\n");
        fflush($this->pipes[0]);
    }

    public function finish(): void
    {
        fclose($this->pipes[0]);
        $errors = stream_get_contents($this->pipes[2]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        // @phpstan-ignore-next-line
        $status = proc_close($this->process);
        $this->process = null;
        Assert::assertSame(0, $status, $errors);
    }

    public function kill(): void
    {
        // @phpstan-ignore-next-line
        proc_terminate($this->process, 9);

        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        // @phpstan-ignore-next-line
        proc_close($this->process);
        $this->process = null;
    }
}
