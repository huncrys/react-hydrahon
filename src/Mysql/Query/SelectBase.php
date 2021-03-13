<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

use Closure;

class SelectBase extends Base
{
    protected array $wheres = [];
    protected ?int $offset = null;
    protected ?int $limit = null;

    protected function stringArgumentToArray(string $argument): array
    {
        if (str_contains($argument, ',')) {
            return array_map('trim', explode(',', $argument));
        }

        return [$argument];
    }

    public function resetWheres(): static
    {
        $this->wheres = [];

        return $this;
    }

    public function resetLimit(): static
    {
        $this->limit = null;
        return $this;
    }

    public function resetOffset(): static
    {
        $this->offset = null;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function where(
        array|Closure|string $column,
        mixed $param1 = null,
        mixed $param2 = null,
        string $type = 'and'
    ): static {
        if ($type !== 'and' && $type !== 'or' && $type !== 'where') {
            throw new Exception(sprintf('Invalid where type "%s"', $type));
        }

        if (empty($this->wheres)) {
            $type = 'where';
        } elseif ($type === 'where') {
            $type = 'and';
        }

        if (is_array($column)) {
            $subquery = new SelectBase($this->connection, $this->translator);
            foreach ($column as $key => $val) {
                $subquery->where($key, $val, null, $type);
            }

            $this->wheres[] = [$type, $subquery];
            return $this;
        }

        if ($column instanceof Closure) {
            $subquery = new SelectBase($this->connection, $this->translator);

            call_user_func_array($column, [&$subquery]);

            $this->wheres[] = [$type, $subquery];
            return $this;
        }

        if (is_null($param2)) {
            $param2 = $param1;
            $param1 = '=';
        }

        if (is_array($param2)) {
            $param2 = array_unique($param2);
        }

        $this->wheres[] = [$type, $column, $param1, $param2];

        return $this;
    }

    /**
     * @throws Exception
     */
    public function orWhere(array|Closure|string $column, mixed $param1 = null, mixed $param2 = null): static
    {
        return $this->where($column, $param1, $param2, 'or');
    }

    /**
     * @throws Exception
     */
    public function andWhere(array|Closure|string $column, mixed $param1 = null, mixed $param2 = null): static
    {
        return $this->where($column, $param1, $param2);
    }

    /**
     * @throws Exception
     */
    public function whereIn(string $column, array $options = []): static
    {
        if (empty($options)) {
            return $this;
        }

        return $this->where($column, 'in', $options);
    }

    /**
     * @throws Exception
     */
    public function whereNotIn(string $column, array $options = []): static
    {
        if (empty($options)) {
            return $this;
        }

        return $this->where($column, 'not in', $options);
    }

    /**
     * @throws Exception
     */
    public function whereNull(string $column): static
    {
        return $this->where($column, 'is', $this->raw('NULL'));
    }

    /**
     * @throws Exception
     */
    public function whereNotNull(string $column): static
    {
        return $this->where($column, 'is not', $this->raw('NULL'));
    }

    /**
     * @throws Exception
     */
    public function orWhereNull(string $column): static
    {
        return $this->orWhere($column, 'is', $this->raw('NULL'));
    }

    /**
     * @throws Exception
     */
    public function orWhereNotNull(string $column): static
    {
        return $this->orWhere($column, 'is not', $this->raw('NULL'));
    }

    public function limit(int $offsetOrLimit, ?int $limit = null): static
    {
        if (!is_null($limit)) {
            $this->offset = $offsetOrLimit;
            $this->limit  = $limit;
        } else {
            $this->limit = $offsetOrLimit;
        }

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function page(int $page, int $size = 25): static
    {
        if ($page < 0) {
            $page = 0;
        }

        $this->limit  = $size;
        $this->offset = $size * $page;

        return $this;
    }
}
