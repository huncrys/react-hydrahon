<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

class Update extends SelectBase
{
    public array $values = [];
    public array $increments = [];
    public array $decrements = [];

    public function set(array|string $column, mixed $value = null): static
    {
        if (empty($column)) {
            return $this;
        }

        if (!is_null($value)) {
            $column = [$column => $value];
        }

        $this->values = array_merge($this->values, $column);

        return $this;
    }

    public function increment(array|string $column, ?int $value = null): static
    {
        if (empty($column)) {
            return $this;
        }

        if (!is_null($value)) {
            $column = [$column => $value];
        } elseif (!is_array($column)) {
            $column = [$column => 1];
        }

        $this->increments = array_merge($this->increments, $column);

        return $this;
    }

    public function decrement(array|string $column, ?int $value = null): static
    {
        if (empty($column)) {
            return $this;
        }

        if (!is_null($value)) {
            $column = [$column => $value];
        } elseif (!is_array($column)) {
            $column = [$column => 1];
        }

        $this->decrements = array_merge($this->decrements, $column);

        return $this;
    }
}
