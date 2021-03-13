<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query\Sql;

class Update extends SelectBase
{
    public array $values = [];

    public function set(array|string $param1, mixed $param2 = null): static
    {
        if (empty($param1)) {
            return $this;
        }
        if (!is_null($param2)) {
            $param1 = [$param1 => $param2];
        }

        $this->values = array_merge($this->values, $param1);

        return $this;
    }
}
