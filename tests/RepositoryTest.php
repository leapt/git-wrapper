<?php

declare(strict_types=1);

namespace Leapt\GitWrapper\Tests;

use Leapt\GitWrapper\Exception\GitRuntimeException;
use Leapt\GitWrapper\Exception\InvalidGitRepositoryDirectoryException;
use Leapt\GitWrapper\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    public function testEmptyGitRepository(): void
    {
        $repository = $this->createEmptyRepository();
        self::assertSame('', $repository->git('branch'));
        self::assertSame([], $repository->getBranches());
        self::assertNull($repository->getCurrentBranch());
        self::assertFalse($repository->hasBranch('main'));

        $this->expectException(GitRuntimeException::class);
        $repository->git('checkout main');
    }

    public function testInitializeInvalidDirectoryMustFail(): void
    {
        $directory = sys_get_temp_dir() . '/leapt-git-wrapper/' . uniqid('', false);
        self::assertDirectoryDoesNotExist($directory . '/.git');

        $this->expectException(InvalidGitRepositoryDirectoryException::class);
        new Repository($directory);
    }
    
    private function createEmptyRepository(array $options = []): Repository
    {
        $directory = sys_get_temp_dir() . '/leapt-git-wrapper/' . uniqid('', false);
        self::assertDirectoryDoesNotExist($directory . '/.git');

        exec('git init ' . escapeshellarg($directory));
        $repository = new Repository($directory, false, $options);

        foreach (['LICENSE', 'composer.json'] as $file) {
            copy(__DIR__ . '/../' . $file, $directory . '/' . $file);
        }

        return $repository;
    }
}
