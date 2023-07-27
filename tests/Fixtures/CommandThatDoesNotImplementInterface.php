<?php

declare(strict_types=1);

namespace Leapt\GitWrapper\Tests\Fixtures;

final class CommandThatDoesNotImplementInterface
{
    public function run(): string
    {
        return '';
    }
}
