<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query\Sql;

class SelectJoin extends SelectBase
{
    protected array $ons = [];

    public function on(string $localKey, string $operator, string $referenceKey, string $type = 'and'): static
    {
        $this->ons[] = [$type, $localKey, $operator, $referenceKey];

        return $this;
    }

    public function orOn(string $localKey, string $operator, string $referenceKey): static
    {
        return $this->on($localKey, $operator, $referenceKey, 'or');
    }

    public function andOn(string $localKey, string $operator, string $referenceKey): static
    {
        return $this->on($localKey, $operator, $referenceKey);
    }
}
