<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

class Table extends Base
{
    public function select(string|array|null $fields = null): Select
    {
        $query = new Select($this->connection, $this->translator, $this);

        return $query->fields($fields);
    }

    public function insert(array $values = []): Insert
    {
        $query = new Insert($this->connection, $this->translator, $this);

        return $query->values($values);
    }

    public function replace(array $values = []): Replace
    {
        $query = new Replace($this->connection, $this->translator, $this);

        return $query->values($values);
    }

    public function update(array $values = []): Update
    {
        $query = new Update($this->connection, $this->translator, $this);

        return $query->set($values);
    }

    public function delete(): Delete
    {
        return new Delete($this->connection, $this->translator, $this);
    }

    public function drop(): Drop
    {
        return new Drop($this->connection, $this->translator, $this);
    }

    public function truncate(): Truncate
    {
        return new Truncate($this->connection, $this->translator, $this);
    }
}