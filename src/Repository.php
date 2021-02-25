<?php

declare(strict_types=1);

namespace Leapt\GitWrapper;

use Leapt\GitWrapper\Exception\InvalidGitRepositoryDirectoryException;

class Repository
{
    private const DATE_FORMAT = 'iso';
    private const LOG_FORMAT = '"%H|%T|%an|%ae|%ad|%cn|%ce|%cd|%s"';
    private const DEFAULT_OPTIONS = [
        'command_class'  => Command::class,
        'git_executable' => '/usr/bin/git',
    ];

    private array $options;

    public function __construct(
        private string $directory,
        private bool $debug = false,
        array $options = [],
    ) {
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->checkIsValidGitRepo();
    }

    /**
     * Helper method to get a list of commits which exist in $sourceBranch that do not yet exist in $targetBranch.
     *
     * @return array formatted list of commits
     */
    public function getDifferenceBetweenBranches(string $targetBranch, string $sourceBranch): array
    {
        $output = $this->git(sprintf('log %s..%s --date=%s --format=format:%s', $targetBranch, $sourceBranch, self::DATE_FORMAT, self::LOG_FORMAT));

        return $this->parseLogsIntoArray($output);
    }

    /**
     * Create a new Git repository in filesystem, running "git init".
     */
    public static function create(string $directory, bool $debug = false, array $options = []): self
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $commandString = $options['git_executable'] . ' init';
        $command = new $options['command_class']($directory, $commandString, $debug);
        $command->run();

        return new self($directory, $debug, $options);
    }

    /**
     * Clone a new Git repository in filesystem, running "git clone".
     */
    public static function cloneUrl(string $url, string $directory, bool $debug = false, array $options = []): self
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $commandString = $options['git_executable'] . ' clone ' . escapeshellarg($url) . ' ' . escapeshellarg($directory);
        $command = new $options['command_class'](getcwd(), $commandString, $debug);
        $command->run();

        return new self($directory, $debug, $options);
    }

    public function getConfiguration(): Configuration
    {
        return new Configuration($this);
    }

    public function getBranches(string $flags = ''): array
    {
        return array_filter(preg_replace('/[\s\*]/', '', explode("\n", $this->git('branch ' . $flags))));
    }

    public function getCurrentBranch(): ?string
    {
        $output = $this->git('branch');

        foreach (explode("\n", $this->git('branch')) as $branchLine) {
            if ('*' === $branchLine[0]) {
                return substr($branchLine, 2);
            }
        }

        return null;
    }

    public function hasBranch(string $branchName): bool
    {
        return \in_array($branchName, $this->getBranches(), true);
    }

    public function getTags(): array
    {
        $output = $this->git('tag');

        return $output ? array_filter(explode("\n", $output)) : [];
    }

    /**
     * Return the result of `git log` formatted in a PHP array.
     */
    public function getCommits(int $nbCommits = 10): array
    {
        $output = $this->git(sprintf('log -n %d --date=%s --format=format:%s', $nbCommits, self::DATE_FORMAT, self::LOG_FORMAT));

        return $this->parseLogsIntoArray($output);
    }

    public function checkIsValidGitRepo(): void
    {
        if (!file_exists($this->directory . '/.git/HEAD')) {
            throw new InvalidGitRepositoryDirectoryException($this->directory . ' is not a valid Git repository');
        }
    }

    /**
     * Run any git command, like "status" or "checkout -b mybranch origin/mybranch".
     */
    public function git(string $commandString): string
    {
        // clean commands that begin with "git "
        $commandString = preg_replace('/^git\s/', '', $commandString);
        $commandString = $this->options['git_executable'] . ' ' . $commandString;

        $command = new $this->options['command_class']($this->directory, $commandString, $this->debug);

        return $command->run();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Convert a formatted log string into an array.
     */
    private function parseLogsIntoArray(string $logOutput): array
    {
        $commits = [];

        foreach (explode("\n", $logOutput) as $line) {
            $infos = explode('|', $line);
            $commits[] = [
                'id'     => $infos[0],
                'tree'   => $infos[1],
                'author' => [
                    'name'  => $infos[2],
                    'email' => $infos[3],
                ],
                'authored_date' => $infos[4],
                'commiter'      => [
                    'name'  => $infos[5],
                    'email' => $infos[6],
                ],
                'committed_date' => $infos[7],
                'message'        => $infos[8],
            ];
        }

        return $commits;
    }
}
