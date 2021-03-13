<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql;

use Crys\Hydrahon\Mysql\Query\Delete;
use Crys\Hydrahon\Mysql\Query\Drop;
use Crys\Hydrahon\Mysql\Query\Exception;
use Crys\Hydrahon\Mysql\Query\Insert;
use Crys\Hydrahon\Mysql\Query\Select;
use Crys\Hydrahon\Mysql\Query\Table;
use Crys\Hydrahon\Mysql\Query\Truncate;
use Crys\Hydrahon\Mysql\Query\Update;

class Builder extends BaseQuery
{
    /**
     * @param string|array|null $table
     * @param string|null $alias
     * @return Table
     * @throws Exception
     */
    public function table(string|array|null $table = null, ?string $alias = null): Table
    {
        $query = new Table($this->connection, $this->translator, $this);
        $query->table($table, $alias);

        return $query;
    }

    /**
     * @throws Exception
     */
    public function select(string|array|null $table = null, string|array|null $fields = null): Select
    {
        return $this->table($table)->select($fields);
    }

    /**
     * @throws Exception
     */
    public function insert(string|array|null $table = null, array $values = []): Insert
    {
        return $this->table($table)->insert($values);
    }

    /**
     * @throws Exception
     */
    public function update(string|array|null $table = null, array $values = []): Update
    {
        return $this->table($table)->update($values);
    }

    /**
     * @throws Exception
     */
    public function delete(string|array|null $table = null): Delete
    {
        return $this->table($table)->delete();
    }

    /**
     * @throws Exception
     */
    public function drop(string|array|null $table = null): Drop
    {
        return $this->table($table)->drop();
    }

    /**
     * @throws Exception
     */
    public function truncate(string|array|null $table = null): Truncate
    {
        return $this->table($table)->truncate();
    }
}
