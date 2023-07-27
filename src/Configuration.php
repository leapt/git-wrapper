<?php

declare(strict_types=1);

namespace Leapt\GitWrapper;

use Leapt\GitWrapper\Exception\GitRuntimeException;

class Configuration
{
    public const USER_NAME = 'user.name';

    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    public function __construct(private Repository $repository)
    {
    }

    public function get(string $configOption, mixed $fallback = null): mixed
    {
        if (\array_key_exists($configOption, $this->configuration)) {
            return $this->configuration[$configOption];
        }

        try {
            $optionValue = $this->repository->git('config --get ' . $configOption);
            $this->configuration[$configOption] = $optionValue;
        } catch (GitRuntimeException) {
            $optionValue = $fallback;
            $this->configuration[$configOption] = null;
        }

        return $optionValue;
    }

    public function set(string $configOption, mixed $configValue): void
    {
        $this->repository->git(sprintf('config --local %s %s', $configOption, $configValue));
        unset($this->configuration[$configOption]);
    }

    public function remove(string $configOption): void
    {
        $this->repository->git(sprintf('config --local --unset %s', $configOption));
        unset($this->configuration[$configOption]);
    }
}
