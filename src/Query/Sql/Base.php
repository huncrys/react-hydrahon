<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query\Sql;

use Closure;
use Crys\Hydrahon\BaseQuery;

class Base extends BaseQuery
{
    protected ?string $database = null;
    protected ?string $table = null;

    protected function inheritFromParent(BaseQuery $parent): void
    {
        parent::inheritFromParent($parent);

        if (isset($parent->database)) {
            $this->database = $parent->database;
        }

        if (isset($parent->table)) {
            $this->table = $parent->table;
        }
    }

    /**
     * @throws Exception
     */
    public function table(array|string|Closure $table, int|string|null $alias = null): static
    {
        $database = null;

        if (is_object($table) && ($table instanceof Closure)) {
            if (is_null($alias)) {
                throw new Exception('You must define an alias when working with subselects.');
            }

            $table = [$alias => $table];
        }

        if (is_array($table) && ($closure = reset($table)) instanceof Closure) {
            $alias = key($table);

            $subquery = new Select();

            call_user_func_array($closure, [&$subquery]);

            $table = [$alias => $subquery];
        } elseif (is_string($table) && str_contains($table, '.')) {
            $selection = explode('.', $table);

            if (count($selection) !== 2) {
                throw new Exception('Invalid argument given. You can only define one separator.');
            }

            [$database, $table] = $selection;
        }

        if (is_string($table)) {
            if (str_contains($table, ' as ')) {
                [$table, $alias] = explode(' as ', $table);
            }

            if (!is_null($alias)) {
                $table = [$table => $alias];
            }
        }

        $this->database = $database;
        $this->table    = $table;

        return $this;
    }
}
