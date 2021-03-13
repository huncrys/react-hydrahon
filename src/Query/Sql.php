<?php namespace Crys\Hydrahon\Query;

/**
 * SQL query object
 ** 
 * @package         Hydrahon
 * @copyright       2015 Mario Döring
 */

use Crys\Hydrahon\BaseQuery;

use Crys\Hydrahon\Query\Sql\Select;
use Crys\Hydrahon\Query\Sql\Insert;
use Crys\Hydrahon\Query\Sql\Update;
use Crys\Hydrahon\Query\Sql\Delete;
use Crys\Hydrahon\Query\Sql\Drop;
use Crys\Hydrahon\Query\Sql\Truncate;
use Crys\Hydrahon\Query\Sql\Table;
use Crys\Hydrahon\Query\Sql\Base;

class Sql extends BaseQuery
{
    /**
     * Create a new table instance
     * 
     *     $h->table('users')
     *
     * @param string|array                              $table
     * @param string                                    $alias
     * @return Table
     */
    public function table($table = null, $alias = null)
    {
        $query = new Table($this); 
        $query->table($table, $alias);

        return $query;
    }

    /**
     * Create a new select query builder
     * 
     *     $h->select('users', ['name', 'age'])
     *
     * @param string|array                              $fields
     * @return Select
     */
    public function select($table = null, $fields = null)
    {
        return $this->table($table)->select($fields);
    }

    /**
     * Create a new insert query builder
     * 
     *     $h->insert('users', ['name' => 'Lucas', 'age' => 21])
     *
     * @param array                                     $values
     * @return Insert
     */
    public function insert($table = null, array $values = array())
    {
        return $this->table($table)->insert($values);
    }

    /**
     * Create a new update query builder
     *
     *     $h->update('users', ['age' => 25])->where('name', 'Johanna')
     *         
     * @param array                                  $values
     * @return Update
     */
    public function update($table = null, array $values = array())
    {
        return $this->table($table)->update($values);
    }

    /**
     * Create a new delete sql builder
     * 
     *     $h->delete('users')->where('age', '<', '18')
     *
     * @return Delete
     */
    public function delete($table = null)
    {
        return $this->table($table)->delete();
    }

    /**
     * Create a new drop table query
     * 
     *     $h->drop('users')
     *
     * @return Drop
     */
    public function drop($table = null)
    {
        return $this->table($table)->drop();
    }

    /**
     * Create a new truncate table query
     * 
     *     $h->truncate('users')
     *
     * @return Truncate
     */
    public function truncate($table = null)
    {
        return $this->table($table)->truncate();
    }
}
