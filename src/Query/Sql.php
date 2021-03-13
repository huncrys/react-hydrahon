<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Query;

use Crys\Hydrahon\BaseQuery;

use Crys\Hydrahon\Query\Sql\Select;
use Crys\Hydrahon\Query\Sql\Insert;
use Crys\Hydrahon\Query\Sql\Update;
use Crys\Hydrahon\Query\Sql\Delete;
use Crys\Hydrahon\Query\Sql\Drop;
use Crys\Hydrahon\Query\Sql\Truncate;
use Crys\Hydrahon\Query\Sql\Table;

class Sql extends BaseQuery
{
    /**
     * @throws Sql\Exception
     */
    public function table(string|array|null $table = null, ?string $alias = null): Table
    {
        $query = new Table($this);
        $query->table($table, $alias);

        return $query;
    }

    /**
     * @throws Sql\Exception
     */
    public function select(string|array|null $table = null, string|array|null $fields = null): Select
    {
        return $this->table($table)->select($fields);
    }

    /**
     * @throws Sql\Exception
     */
    public function insert(string|array|null $table = null, array $values = []): Insert
    {
        return $this->table($table)->insert($values);
    }

    /**
     * @throws Sql\Exception
     */
    public function update(string|array|null $table = null, array $values = []): Update
    {
        return $this->table($table)->update($values);
    }

    /**
     * @throws Sql\Exception
     */
    public function delete(string|array|null $table = null): Delete
    {
        return $this->table($table)->delete();
    }

    /**
     * @throws Sql\Exception
     */
    public function drop(string|array|null $table = null): Drop
    {
        return $this->table($table)->drop();
    }

    /**
     * @throws Sql\Exception
     */
    public function truncate(string|array|null $table = null): Truncate
    {
        return $this->table($table)->truncate();
    }
}
