<?php

declare(strict_types=1);

namespace Leapt\GitWrapper;

interface CommandInterface
{
    public function run(): string;
}
