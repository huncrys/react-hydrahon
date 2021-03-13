<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

class Func
{
    protected string $name;

    protected array $arguments = array();

    public function __construct(string $name, mixed ...$arguments)
    {
        $this->name      = $name;
        $this->arguments = $arguments;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }
}
