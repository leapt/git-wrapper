<?php

declare(strict_types=1);

namespace Leapt\GitWrapper\Tests;

use Leapt\GitWrapper\Repository;
use PHPUnit\Framework\TestCase;

final class CommandTest extends TestCase
{
    public function testNoDebugOutputsNothing(): void
    {
        $repository = $this->createEmptyRepository(false);
        ob_start();
        $repository->git('status');
        $output = ob_get_clean();
        self::assertSame('', $output);
    }

    public function testDebugOutputsSomething(): void
    {
        $repository = $this->createEmptyRepository(true);
        ob_start();
        $repository->git('status');
        $output = ob_get_clean();
        self::assertStringContainsString('&& /usr/bin/git status', $output);
    }

    private function createEmptyRepository(bool $debug): Repository
    {
        $directory = sys_get_temp_dir() . '/leapt-git-wrapper/' . uniqid('', false);
        self::assertDirectoryDoesNotExist($directory . '/.git');

        exec('git init ' . escapeshellarg($directory));

        return new Repository($directory, $debug);
    }
}
