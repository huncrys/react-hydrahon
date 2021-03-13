<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

use Closure;
use Crys\Hydrahon\BaseQuery;
use Crys\Hydrahon\Exception as BaseException;
use Crys\Hydrahon\Mysql\Expression;
use React\Promise\PromiseInterface;

class Select extends SelectBase implements FetchableInterface
{
    protected array $fields = [];
    protected bool $distinct = false;
    protected array $orders = [];
    protected array $groups = [];
    protected array $joins = [];
    protected string|false $groupResults = false;
    protected string|false $forwardKey = false;

    protected function inheritFromParent(BaseQuery $parent): void
    {
        parent::inheritFromParent($parent);

        /** @noinspection SelfClassReferencingInspection */
        if ($parent instanceof Select) {
            $parent->copyTo($this);
        }
    }

    public function copyTo(Select $query): void
    {
        $query->fields       = $this->fields;
        $query->distinct     = $this->distinct;
        $query->orders       = $this->orders;
        $query->groups       = $this->groups;
        $query->joins        = $this->joins;
        $query->groupResults = $this->groupResults;
        $query->forwardKey   = $this->forwardKey;
    }

    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function fields(object|array|string $fields): static
    {
        $this->fields = [];

        if (is_string($fields)) {
            $fields = $this->stringArgumentToArray($fields);
        } elseif (is_object($fields)) {
            return $this->addField($fields);
        }

        if (empty($fields) || $fields === ['*'] || $fields === ['']) {
            return $this;
        }

        foreach ($fields as $key => $field) {
            if (is_string($key)) {
                $this->addField($key, $field);
            } else {
                $this->addField($field);
            }
        }

        return $this;
    }

    public function addField(object|string $field, ?string $alias = null): static
    {
        $this->fields[] = [$field, $alias];

        return $this;
    }

    public function addFieldCount(string $field, ?string $alias = null): static
    {
        return $this->addField(new Func('count', $field), $alias);
    }

    public function addFieldMax(string $field, ?string $alias = null): static
    {
        return $this->addField(new Func('max', $field), $alias);
    }

    public function addFieldMin(string $field, ?string $alias = null): static
    {
        return $this->addField(new Func('min', $field), $alias);
    }

    public function addFieldSum(string $field, ?string $alias = null): static
    {
        return $this->addField(new Func('sum', $field), $alias);
    }

    public function addFieldAvg(string $field, ?string $alias = null): static
    {
        return $this->addField(new Func('avg', $field), $alias);
    }

    public function addFieldRound(string $field, int $decimals = 0, ?string $alias = null): static
    {
        return $this->addField(new Func('round', $field, new Expression($decimals)), $alias);
    }

    public function orderBy(array|string|Expression $columns, string $direction = 'asc'): static
    {
        if (is_string($columns)) {
            $columns = $this->stringArgumentToArray($columns);
        } elseif ($columns instanceof Expression) {
            $this->orders[] = [$columns, $direction];
            return $this;
        }

        foreach ($columns as $key => $column) {
            if (is_numeric($key)) {
                if ($column instanceof Expression) {
                    $this->orders[] = [$column, $direction];
                } else {
                    $this->orders[$column] = $direction;
                }
            } else {
                $this->orders[$key] = $column;
            }
        }

        return $this;
    }

    public function groupBy(array|string $groupKeys): static
    {
        if (is_string($groupKeys)) {
            $groupKeys = $this->stringArgumentToArray($groupKeys);
        }

        foreach ($groupKeys as $groupKey) {
            $this->groups[] = $groupKey;
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function join(
        array|string $table,
        Closure|string $localKey,
        ?string $operator = null,
        ?string $referenceKey = null,
        string $type = 'left'
    ): static {
        if (!in_array($type, ['inner', 'left', 'right', 'outer'])) {
            throw new Exception(
                sprintf(
                    'Invalid join type "%s" given. Available type: inner, left, right, outer',
                    $type
                )
            );
        }

        if (is_object($localKey) && ($localKey instanceof Closure)) {
            $subquery = new SelectJoin($this->connection, $this->translator);

            call_user_func_array($localKey, [&$subquery]);

            $this->joins[] = [$type, $table, $subquery];

            return $this;
        }

        $this->joins[] = [$type, $table, $localKey, $operator, $referenceKey];

        return $this;
    }

    /**
     * @throws Exception
     */
    public function leftJoin(
        array|string $table,
        string $localKey,
        ?string $operator = null,
        ?string $referenceKey = null
    ): static {
        return $this->join($table, $localKey, $operator, $referenceKey);
    }

    /**
     * @throws Exception
     */
    public function rightJoin(
        array|string $table,
        string $localKey,
        ?string $operator = null,
        ?string $referenceKey = null
    ): static {
        return $this->join($table, $localKey, $operator, $referenceKey, 'right');
    }

    /**
     * @throws Exception
     */
    public function innerJoin(
        array|string $table,
        string $localKey,
        ?string $operator = null,
        ?string $referenceKey = null
    ): static {
        return $this->join($table, $localKey, $operator, $referenceKey, 'inner');
    }

    /**
     * @throws Exception
     */
    public function outerJoin(
        array|string $table,
        string $localKey,
        ?string $operator = null,
        ?string $referenceKey = null
    ): static {
        return $this->join($table, $localKey, $operator, $referenceKey, 'outer');
    }

    public function forwardKey(string|false $key = 'id'): static
    {
        if ($key === false) {
            $this->forwardKey = false;
        } else {
            $this->forwardKey = $key;
        }

        return $this;
    }

    public function groupResults(string|false $key): static
    {
        if ($key === false) {
            $this->groupResults = false;
        } else {
            $this->groupResults = $key;
        }

        return $this;
    }

    public function get(): PromiseInterface
    {
        return $this->run()
            ->then(
                function (array $results): mixed {
                    if (!is_array($results) || empty($results)) {
                        return [];
                    }

                    if ($this->forwardKey !== false && is_string($this->forwardKey)) {
                        $rawResults = $results;
                        $results    = [];

                        if (!is_array(reset($rawResults))) {
                            throw new Exception('Cannot forward key, the result is no associated array.');
                        }

                        foreach ($rawResults as $result) {
                            $results[$result[$this->forwardKey]] = $result;
                        }
                    }

                    if ($this->groupResults !== false && is_string($this->groupResults)) {
                        $rawResults = $results;
                        $results    = [];

                        if (!is_array(reset($rawResults))) {
                            throw new Exception('Cannot forward key, the result is no associated array.');
                        }

                        foreach ($rawResults as $key => $result) {
                            $results[$result[$this->groupResults]][$key] = $result;
                        }
                    }

                    if ($this->limit === 1) {
                        return reset($results);
                    }

                    return $results;
                }
            );
    }

    public function one(): PromiseInterface
    {
        return $this->limit(0, 1)->get();
    }

    /**
     * @throws Exception
     * @throws BaseException
     */
    public function find(mixed $id, string|array|Closure $key = 'id'): PromiseInterface
    {
        return $this->where($key, $id)->one();
    }

    public function first(string|array|Closure $key = 'id'): PromiseInterface
    {
        return $this->orderBy($key)->one();
    }

    public function last(string|array|Closure $key = 'id'): PromiseInterface
    {
        return $this->orderBy($key, 'desc')->one();
    }

    public function column(Func|string|Expression $column): PromiseInterface
    {
        return $this->fields($column)->one()->then(
            static function (array $result): mixed {
                return !empty($result)
                    ? reset($result)
                    : null;
            }
        );
    }

    public function count(string|array|Closure $field = '*'): PromiseInterface
    {
        return $this->column(new Func('count', $field))->then(
            static function (mixed $result): ?int {
                if ($result !== null) {
                    return (int)$result;
                }

                return null;
            }
        );
    }

    public function sum(string|array|Closure $field = '*'): PromiseInterface
    {
        return $this->column(new Func('sum', $field))->then(
            static function (mixed $result): ?float {
                if ($result !== null) {
                    return (float)$result;
                }

                return null;
            }
        );
    }

    public function max(string|array|Closure $field = '*'): PromiseInterface
    {
        return $this->column(new Func('max', $field))->then(
            static function (mixed $result): ?float {
                if ($result !== null) {
                    return (float)$result;
                }

                return null;
            }
        );
    }

    public function min(string|array|Closure $field = '*'): PromiseInterface
    {
        return $this->column(new Func('min', $field))->then(
            static function (mixed $result): ?float {
                if ($result !== null) {
                    return (float)$result;
                }

                return null;
            }
        );
    }

    public function avg(string|array|Closure $field = '*'): PromiseInterface
    {
        return $this->column(new Func('avg', $field))->then(
            static function (mixed $result): ?float {
                if ($result !== null) {
                    return (float)$result;
                }

                return null;
            }
        );
    }

    public function exists(): PromiseInterface
    {
        $existsQuery = new Exists($this->connection, $this->translator, $this);
        $existsQuery->setSelect($this);

        return $existsQuery
            ->run()
            ->then(
                function (array $results): bool {
                    if (!empty($results) && ($first = reset($results)) !== false && isset($first['exists'])) {
                        return (bool)$first['exists'];
                    }

                    return false;
                }
            );
    }
}
