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

    /**
     * @var array<string, string>
     */
    private array $options;

    /**
     * @param array<string, string> $options
     */
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
     * @return array<array<mixed>> formatted list of commits
     */
    public function getDifferenceBetweenBranches(string $targetBranch, string $sourceBranch): array
    {
        $output = $this->git(sprintf('log %s..%s --date=%s --format=format:%s', $targetBranch, $sourceBranch, self::DATE_FORMAT, self::LOG_FORMAT));

        return $this->parseLogsIntoArray($output);
    }

    /**
     * Create a new Git repository in filesystem, running "git init".
     *
     * @param array<string, string> $options
     */
    public static function create(string $directory, bool $debug = false, array $options = []): self
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $commandString = $options['git_executable'] . ' init';
        $command = self::createCommand($options['command_class'], $directory, $commandString, $debug);
        \assert(method_exists($command, 'run'));
        $command->run();

        return new self($directory, $debug, $options);
    }

    /**
     * Clone a new Git repository in filesystem, running "git clone".
     *
     * @param array<string, string> $options
     */
    public static function cloneUrl(string $url, string $directory, bool $debug = false, array $options = []): self
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        $commandString = $options['git_executable'] . ' clone ' . escapeshellarg($url) . ' ' . escapeshellarg($directory);
        $command = self::createCommand($options['command_class'], (string) getcwd(), $commandString, $debug);
        \assert(method_exists($command, 'run'));
        $command->run();

        return new self($directory, $debug, $options);
    }

    public function getConfiguration(): Configuration
    {
        return new Configuration($this);
    }

    /**
     * @return array<array-key, string>
     */
    public function getBranches(string $flags = ''): array
    {
        return array_filter(preg_replace('/[\s\*]/', '', explode("\n", $this->git('branch ' . $flags))));
    }

    public function getCurrentBranch(): ?string
    {
        $output = $this->git('branch');

        if ('' !== $output) {
            foreach (explode("\n", $output) as $branchLine) {
                if ('*' === $branchLine[0]) {
                    return substr($branchLine, 2);
                }
            }
        }

        return null;
    }

    public function hasBranch(string $branchName): bool
    {
        return \in_array($branchName, $this->getBranches(), true);
    }

    /**
     * @return array<array-key, string>
     */
    public function getTags(): array
    {
        $output = $this->git('tag');

        return $output ? array_filter(explode("\n", $output)) : [];
    }

    /**
     * Return the result of `git log` formatted in a PHP array.
     *
     * @return array<array<mixed>>
     */
    public function getCommits(int $nbCommits = 10): array
    {
        $output = $this->git(sprintf('log -n %d --date=%s --format=format:%s', $nbCommits, self::DATE_FORMAT, self::LOG_FORMAT));

        return $this->parseLogsIntoArray($output);
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function getLastCommit(): array
    {
        $output = $this->git(sprintf('log -n 1 --date=%s --format=format:%s', self::DATE_FORMAT, self::LOG_FORMAT));

        return $this->parseLogsIntoArray($output)[0];
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

        $command = self::createCommand($this->options['command_class'], $this->directory, $commandString, $this->debug);
        \assert(method_exists($command, 'run'));

        return $command->run();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @param class-string $commandClass
     */
    public static function createCommand(string $commandClass, string $directory, string $commandString, bool $debug): object
    {
        if (!\in_array(CommandInterface::class, class_implements($commandClass), true)) {
            trigger_deprecation('leapt/git-wrapper', '1.3', 'Passing a Command class that does not implement "%s" is deprecated.', CommandInterface::class);

            if (!method_exists($commandClass, 'run')) {
                throw new \RuntimeException(sprintf('The Command class must implement a "public function run(): string" method, the "%s" class does not.', $commandClass));
            }
        }

        return new $commandClass($directory, $commandString, $debug);
    }

    /**
     * Convert a formatted log string into an array.
     *
     * @return array<array-key, array<string, string|array<string, string>>>
     */
    private function parseLogsIntoArray(string $logOutput): array
    {
        $commits = [];

        foreach (explode("\n", $logOutput) as $line) {
            $infos = explode('|', $line);
            $commits[] = [
                'id'             => $infos[0],
                'tree'           => $infos[1],
                'author'         => [
                    'name'  => $infos[2],
                    'email' => $infos[3],
                ],
                'authored_date'  => $infos[4],
                'committer'      => [
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
