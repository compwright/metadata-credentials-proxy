<?php

namespace Compwright\DockerEc2Metadata;

class Container
{
    private string $name;

    /** @var string[] */
    private array $env;

    /**
     * @param string[] $env
     */
    public function __construct(string $name, array $env = [])
    {
        $this->name = $name;
        $this->env = $env;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasEnvValue(string $key): bool
    {
        foreach ($this->env as $param) {
            $var = explode('=', $param)[0];
            if ($key === $var) {
                return true;
            }
        }

        return false;
    }

    public function getEnvValue(string $key): ?string
    {
        foreach ($this->env as $param) {
            list($var, $val) = explode('=', $param);
            if ($key === $var) {
                return $val;
            }
        }

        return null;
    }
}
