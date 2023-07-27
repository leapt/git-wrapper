<?php

declare(strict_types=1);

namespace Leapt\GitWrapper;

use Leapt\GitWrapper\Exception\GitRuntimeException;
use Symfony\Component\Process\Process;

class Command implements CommandInterface
{
    private string $commandString;

    public function __construct(
        private string $directory,
        string $commandString,
        private bool $debug,
    ) {
        $this->commandString = trim($commandString);
    }

    public function run(): string
    {
        $commandToRun = sprintf('cd %s && %s', escapeshellarg($this->directory), $this->commandString);

        if ($this->debug) {
            echo $commandToRun . "\n";
        }

        $process = Process::fromShellCommandline($commandToRun);
        $process->run();

        if ($this->debug) {
            echo $process->getOutput() . "\n";
        }

        if (!$process->isSuccessful()) {
            // Git 1.5.x returns 1 when running "git status"
            if (1 === $process->getExitCode() && 0 === strncmp($this->commandString, 'git status', 10)) {
                // it's ok
            } else {
                $exitCode = $process->getExitCode() ?? 255;
                throw new GitRuntimeException(sprintf('Command %s failed with code %s: %s', $commandToRun, $exitCode, $process->getErrorOutput()), $exitCode);
            }
        }

        return trim($process->getOutput());
    }
}
