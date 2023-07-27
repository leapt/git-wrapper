<?php

declare(strict_types=1);

namespace Leapt\GitWrapper\Tests;

use Leapt\GitWrapper\Configuration;
use Leapt\GitWrapper\Exception\GitRuntimeException;
use Leapt\GitWrapper\Exception\InvalidGitRepositoryDirectoryException;
use Leapt\GitWrapper\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    public function testValidateEmptyGitRepository(): void
    {
        $repository = $this->createEmptyRepository();
        self::assertSame('', $repository->git('branch'));
        self::assertSame([], $repository->getBranches());
        self::assertNull($repository->getCurrentBranch());
        self::assertFalse($repository->hasBranch('main'));

        $this->expectException(GitRuntimeException::class);
        $repository->git('checkout main');
    }

    public function testInvalidCommandFails(): void
    {
        $repository = $this->createEmptyRepository();
        $this->expectException(GitRuntimeException::class);
        $repository->git('unknown');
    }

    public function testValidateGitExecutableOption(): void
    {
        // Valid git binary
        $repository = $this->createEmptyRepository(['git_executable' => '/usr/bin/git']);
        self::assertStringContainsString('main', $repository->git('status'));

        // Invalid git binary
        $this->expectException(GitRuntimeException::class);
        $repository = $this->createEmptyRepository(['git_executable' => '/usr/bin/git-foobar']);
        $repository->git('status');
    }

    public function testCreateRepositoryOnExistingDirectoryMustFail(): void
    {
        $directory = $this->getTempDirectoryPath();
        mkdir($directory);
        $this->expectException(InvalidGitRepositoryDirectoryException::class);
        new Repository($directory);
    }

    public function testSettingConfigurationSucceeds(): void
    {
        $repository = $this->createEmptyRepository();
        $config = $repository->getConfiguration();
        self::assertTrue($config->get('core.editor', true));
        $config->set('core.editor', 'nano');
        self::assertSame('nano', $config->get('core.editor'));
        $config->remove('core.editor');
        self::assertTrue($config->get('core.editor', true));
    }

    public function testCommittingSucceeds(): void
    {
        $repository = $this->createEmptyRepository();
        file_put_contents($repository->getDirectory() . '/README.md', 'No, finally, do not read me.');
        $repository->git('add README.md');
        $repository->git('commit -m "Add README.md"');
        unlink($repository->getDirectory() . '/README.md');
        $repository->git('rm README.md');
        $repository->git('commit -m "Remove README.md"');
        $logs = $repository->getCommits(7);
        self::assertIsArray($logs);
        self::assertCount(2, $logs);

        $config = $repository->getConfiguration();
        $lastCommit = $logs[0];
        self::assertIsArray($lastCommit);
        self::assertSame('Remove README.md', $lastCommit['message']);
        self::assertSame($config->get(Configuration::USER_NAME), $lastCommit['author']['name']);
        self::assertSame($config->get(Configuration::USER_NAME), $lastCommit['committer']['name']);

        $firstCommit = $logs[1];
        self::assertSame('Add README.md', $firstCommit['message']);

        // Test tags
        self::assertSame([], $repository->getTags());

        $repository->git('tag -am "tag 1" first_tag');
        $repository->git('tag -am "tag 2" second_tag');
        self::assertSame(['first_tag', 'second_tag'], $repository->getTags());

        // Test difference between branches
        $repository->git('checkout -b test');
        self::assertSame('test', $repository->getCurrentBranch());

        file_put_contents($repository->getDirectory() . '/CHANGELOG.md', 'Nothing yet.');
        $repository->git('add CHANGELOG.md');
        $repository->git('commit -m "Add CHANGELOG.md"');
        $logs = $repository->getDifferenceBetweenBranches('main', 'test');
        self::assertCount(1, $logs);
        self::assertSame('Add CHANGELOG.md', $logs[0]['message']);
    }

    public function testGetLastCommitReturnsLastCommit(): void
    {
        $repository = $this->createEmptyRepository();
        file_put_contents($repository->getDirectory() . '/README.md', 'No, finally, do not read me.');
        $repository->git('add README.md');
        $repository->git('commit -m "Add README.md"');
        unlink($repository->getDirectory() . '/README.md');
        $repository->git('rm README.md');
        $repository->git('commit -m "Remove README.md"');
        $lastCommit = $repository->getLastCommit();
        self::assertIsArray($lastCommit);
        self::assertArrayHasKey('id', $lastCommit);
        self::assertArrayHasKey('author', $lastCommit);
        self::assertArrayHasKey('committed_date', $lastCommit);
        self::assertSame('Remove README.md', $lastCommit['message']);
    }

    public function testCreate(): void
    {
        $directory = $this->getTempDirectoryPath();
        mkdir($directory);
        self::assertDirectoryDoesNotExist($directory . '/.git');
        Repository::create($directory);
        self::assertDirectoryExists($directory . '/.git');
    }

    public function testValidateCorrectRepository(): void
    {
        $repository = $this->createEmptyRepository();
        $repository->git('remote add origin https://github.com/leapt/git-wrapper.git');
        $repository->git('fetch origin 1.x:1.x --update-head-ok');
        self::assertSame(['1.x'], $repository->getBranches());
        self::assertTrue($repository->hasBranch('1.x'));

        $repository->git('checkout 1.x');
        self::assertSame('1.x', $repository->getCurrentBranch());

        $repository->git('checkout -b other_branch');
        self::assertSame(['1.x', 'other_branch'], $repository->getBranches());
        self::assertTrue($repository->hasBranch('other_branch'));
        self::assertSame('other_branch', $repository->getCurrentBranch());

        $repository->git('checkout 1.x');
        self::assertSame('1.x', $repository->getCurrentBranch());

        // Check usage adding "git" at the beginning of the command
        $repository->git('git checkout other_branch');
        self::assertSame('other_branch', $repository->getCurrentBranch());
    }

    public function testCloneUrl(): void
    {
        $directory = $this->getTempDirectoryPath();
        $repository = Repository::cloneUrl('https://github.com/leapt/git-wrapper.git', $directory);
        self::assertSame('1.x', $repository->getCurrentBranch());
    }

    public function testInitializeInvalidDirectoryMustFail(): void
    {
        $directory = $this->getTempDirectoryPath();
        self::assertDirectoryDoesNotExist($directory . '/.git');

        $this->expectException(InvalidGitRepositoryDirectoryException::class);
        new Repository($directory);
    }

    /**
     * @param array<string, string> $options
     */
    private function createEmptyRepository(array $options = []): Repository
    {
        $directory = $this->getTempDirectoryPath();
        self::assertDirectoryDoesNotExist($directory . '/.git');

        exec('git init ' . escapeshellarg($directory));

        return new Repository($directory, false, $options);
    }

    private function getTempDirectoryPath(): string
    {
        return sys_get_temp_dir() . '/leapt-git-wrapper/' . uniqid('', false);
    }
}
