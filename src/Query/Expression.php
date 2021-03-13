<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query;

class Expression
{
    public function __construct(
        protected int|float|string $value
    ) {
    }

    public function value(): int|float|string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
