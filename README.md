# Leapt Git Wrapper

[![Package version](https://img.shields.io/packagist/v/leapt/git-wrapper.svg?style=flat-square)](https://packagist.org/packages/leapt/git-wrapper)
[![Build Status](https://img.shields.io/github/workflow/status/leapt/git-wrapper/Continuous%20Integration/main?style=flat-square)](https://github.com/leapt/git-wrapper/actions?query=workflow%3A%22Continuous+Integration%22)
![PHP Version](https://img.shields.io/packagist/php-v/leapt/git-wrapper.svg?branch=main&style=flat-square)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)
[![Code coverage](https://img.shields.io/codecov/c/github/leapt/git-wrapper?style=flat-square)](https://codecov.io/gh/leapt/git-wrapper/branch/main)

Allows to manage a Git repository with PHP.
Provides an object-oriented wrapper to run any Git command.

## Requirements

- PHP >= 8.0
- Git >= 1.5

## Instantiate a Repository

```php
use Leapt\GitWrapper\Repository;
$repository = new Repository('/path/to/the/git/repo');
```

It does NOT create a Git repo, but a PHP object to manipulate an existing Git repo.

## Create a Git repository

If the Git repository does not exist yet on file system, `Repository` can create it for you.

```php
$repository = Repository::create('/path/to/the/git/repo');
```

It runs `git init` and returns a `Repository` object.

## Run git commands

`git` commands can be run with the same syntax as in the CLI. Some examples:

```php
// change current branch to main
$repository->git('checkout main');

// pull from a remote
$repository->git('pull origin main');

// add a remote repo
$repository->git('remote add origin https://github.com/leapt/git-wrapper.git');
```

There are no limitations, you can run any git commands.

The `git()` method returns the output string:

```php
echo $repository->git('log --oneline');
```

```
e30b70b Move test repo to system tmp dir, introduce PHPGit_Command
01fabb1 Add test repo
12a95e6 Add base class with basic unit test
58e7769 Fix readme
c14c9ec Initial commit
```

The `git()` method throws a `GitRuntimeException` if the command is invalid:

```php
$repository->git('unknown'); // this git command does NOT exist: throw GitRuntimeException
```

## Get branches information

Some shortcut methods are provided to deal with branches in a convenient way.

### Get the branches list

```php
$branches = $repository->getBranches();
// returns ['main', 'other_branch']
```

### Get the current branch

```php
$branch = $repository->getCurrentBranch();
// returns 'main'
```

### Know if the repo has a given branch

```php
$hasBranch = $repository->hasBranch('main');
// returns true
```

## Get tags information

### Get the tags list:

```php
$tags = $repository->getTags();
// returns ['first_release', 'v2']
```

## Get commits information

You can get an array of the last commits on the current branch.

```php
$commits = $repository->getCommits(15);
// returns an array of the 15 last commits
```

Internally, this method runs `git log` with formatted output. The return value should look like:

```php
    Array
    (
        [0] => Array
            (
                [id] => affb0e84a11b4180b0fa0e5d36bdac73584f0d71
                [tree] => 4b825dc642cb6eb9a060e54bf8d69288fbee4904
                [author] => Array
                    (
                        [name] => ornicar
                        [email] => myemail@gmail.com
                    )

                [authored_date] => 2010-09-22 19:17:35 +0200
                [committer] => Array
                    (
                        [name] => ornicar
                        [email] => myemail@gmail.com
                    )

                [committed_date] => 2010-09-22 19:17:35 +0200
                [message] => My commit message
            )

        [1] => Array
            (
                ...
```

The first commit is the most recent one.

## Debug mode

`Repository` constructor's second parameter lets you enable debug mode.
When debug mode is on, commands and their output are displayed.

```php
$repository = new Repository('/path/to/the/git/repo', true);
```

## Configure

`Repository` can be configured by passing an array of options to the constructor's third parameter.

### Change git executable path

You may need to provide the path to the git executable.

```php
$repo = new Repository('/path/to/the/git/repo', false, ['git_executable' => '/usr/bin/git']);
```

On most Unix system, it's `/usr/bin/git`. On Windows, it may be `C:\Program Files\Git\bin`.

### Change the command class

By default, the `Repository` class will use the `Command` class to implement Git commands.
By replacing this option, you can use your own command implementation:

```php
use Leapt\GitWrapper\Repository;
$repository = new Repository('/path/to/the/git/repo', false, ['command_class' => YourCommand::class]);
```

## Contributing

Feel free to contribute, like sending [pull requests](https://github.com/leapt/git-wrapper/pulls) to add features/tests
or [creating issues](https://github.com/leapt/git-wrapper/issues) :)

Note there are a few helpers to maintain code quality, that you can run using these commands:

```bash
composer cs:dry # Code style check
composer phpstan # Static analysis
vendor/bin/phpunit # Run test
composer test # An alias to run tests
```

## History

This package is a maintained fork of the [ornicar/php-git-repo](https://github.com/ornicar/php-git-repo) package.
