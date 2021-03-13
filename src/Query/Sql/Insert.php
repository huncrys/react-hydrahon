<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query\Sql;

class Insert extends Base
{
    protected array $values = [];
    protected bool $ignore = false;

    public function ignore(bool $ignore = true): static
    {
        $this->ignore = $ignore;
        return $this;
    }

    public function values(array $values = []): static
    {
        if (empty($values)) {
            return $this;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        foreach ($values as $key => $value) {
            ksort($value);
            $values[$key] = $value;
        }

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function resetValues(): static
    {
        $this->values = [];

        return $this;
    }
}
