<?php

declare(strict_types=1);

namespace Leapt\GitWrapper;

use Leapt\GitWrapper\Exception\GitRuntimeException;

class Command
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

        ob_start();
        passthru($commandToRun, $returnVar);
        $output = ob_get_clean();

        if ($this->debug) {
            echo $output . "\n";
        }

        if (0 !== $returnVar) {
            // Git 1.5.x returns 1 when running "git status"
            if (1 === $returnVar && 0 === strncmp($this->commandString, 'git status', 10)) {
                // it's ok
            } else {
                throw new GitRuntimeException(sprintf('Command %s failed with code %s: %s', $commandToRun, $returnVar, $output), $returnVar);
            }
        }

        return trim($output);
    }
}
