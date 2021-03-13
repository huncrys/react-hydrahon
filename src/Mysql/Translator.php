<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql;

use Crys\Hydrahon\BaseQuery;
use Crys\Hydrahon\Exception;
use Crys\Hydrahon\Mysql\Query\Delete;
use Crys\Hydrahon\Mysql\Query\Drop;
use Crys\Hydrahon\Mysql\Query\Exists;
use Crys\Hydrahon\Mysql\Query\Func;
use Crys\Hydrahon\Mysql\Query\Insert;
use Crys\Hydrahon\Mysql\Query\Replace;
use Crys\Hydrahon\Mysql\Query\Select;
use Crys\Hydrahon\Mysql\Query\Truncate;
use Crys\Hydrahon\Mysql\Query\Update;
use Crys\Hydrahon\TranslatorInterface;

class Translator implements TranslatorInterface
{
    protected array $parameters = [];
    protected array $attributes = [];

    /**
     * @throws Exception
     */
    public function translate(BaseQuery $query): array
    {
        $this->attributes = $query->attributes();

        if ($query instanceof Select) {
            $queryString = $this->translateSelect();
        } elseif ($query instanceof Replace) {
            $queryString = $this->translateInsert('replace');
        } elseif ($query instanceof Insert) {
            $queryString = $this->translateInsert('insert');
        } elseif ($query instanceof Update) {
            $queryString = $this->translateUpdate();
        } elseif ($query instanceof Delete) {
            $queryString = $this->translateDelete();
        } elseif ($query instanceof Drop) {
            $queryString = $this->translateDrop();
        } elseif ($query instanceof Truncate) {
            $queryString = $this->translateTruncate();
        } elseif ($query instanceof Exists) {
            $queryString = $this->translateExists();
        } else {
            throw new Exception('Unknown query type. Cannot translate: ' . get_class($query));
        }

        $queryParameters = $this->parameters;
        $this->clearParameters();

        return [$queryString, $queryParameters];
    }

    protected function attr(string $key): mixed
    {
        return $this->attributes[$key];
    }

    protected function clearParameters(): void
    {
        $this->parameters = [];
    }

    protected function addParameter(mixed $value): void
    {
        $this->parameters[] = $value;
    }

    protected function param(mixed $value): mixed
    {
        if ($value instanceof Expression) {
            $this->addParameter($value);

            return '?';
        }

        return $value;
    }

    /**
     * @throws Exception
     */
    protected function escape(object|string $string): string|int|float
    {
        if (is_object($string)) {
            if ($string instanceof Expression) {
                return $string->value();
            }

            if ($string instanceof Func) {
                return $this->escapeFunction($string);
            }

            throw new Exception('Cannot translate object of class: ' . get_class($string));
        }

        if (str_contains($string, ' as ')) {
            [$table, $alias] = explode(' as ', $string);

            return $this->escape(trim($table)) . ' as ' . $this->escape(trim($alias));
        }

        if (str_contains($string, '.')) {
            $string = explode('.', $string);

            foreach ($string as $key => $item) {
                $string[$key] = $this->escapeIdentifier($item);
            }

            return implode('.', $string);
        }

        return $this->escapeIdentifier($string);
    }

    public function escapeIdentifier(mixed $identifier): string
    {
        return '`' . str_replace(array('`', "\0"), array('``', ''), $identifier) . '`';
    }

    /**
     * @throws Exception
     */
    protected function escapeFunction(Func $function): string
    {
        $buffer = $function->name() . '(';

        $arguments = $function->arguments();

        foreach ($arguments as &$argument) {
            $argument = $this->escape($argument);
        }

        return $buffer . implode(', ', $arguments) . ')';
    }

    /**
     * @throws Exception
     */
    protected function escapeList(array $array): string
    {
        foreach ($array as $key => $item) {
            $array[$key] = $this->escape($item);
        }

        return implode(', ', $array);
    }

    /**
     * @throws Exception
     */
    protected function escapeTable(bool $allowAlias = true): string
    {
        $table    = $this->attr('table');
        $database = $this->attr('database');
        $buffer   = '';

        if (!is_null($database)) {
            $buffer .= $this->escape($database) . '.';
        }

        if (is_array($table)) {
            reset($table);

            if ($table[key($table)] instanceof Select) {
                $translator = new static();

                [$subQuery, $subQueryParameters] = $translator->translate($table[key($table)]);

                foreach ($subQueryParameters as $parameter) {
                    $this->addParameter($parameter);
                }

                return '(' . $subQuery . ') as ' . $this->escape(key($table));
            }

            if ($allowAlias) {
                $table = key($table) . ' as ' . $table[key($table)];
            } else {
                $table = key($table);
            }
        }

        return $buffer . $this->escape($table);
    }

    protected function parameterize(array $params): string
    {
        foreach ($params as $key => $param) {
            $params[$key] = $this->param($param);
        }

        return implode(', ', $params);
    }

    /**
     * @throws Exception
     */
    protected function translateInsert(string $key): string
    {
        $build = ($this->attr('ignore') ? $key . ' ignore' : $key);

        $build .= ' into ' . $this->escapeTable(false) . ' ';

        if (!$valueCollection = $this->attr('values')) {
            throw new Exception('Cannot build insert query without values.');
        }

        $build .= '(' . $this->escapeList(array_keys(reset($valueCollection))) . ') values ';

        foreach ($valueCollection as $values) {
            $build .= '(' . $this->parameterize($values) . '), ';
        }

        return substr($build, 0, -2);
    }

    /**
     * @throws Exception
     */
    protected function translateUpdate(): string
    {
        $build = 'update ' . $this->escapeTable() . ' set ';

        foreach ($this->attr('values') as $key => $value) {
            $build .= $this->escape($key) . ' = ' . $this->param($value) . ', ';
        }

        foreach ($this->attr('increments') as $key => $value) {
            $build .= $this->escape($key) . ' = ' . $this->escape($key) . ' + ' . $this->param($value) . ', ';
        }

        foreach ($this->attr('decrements') as $key => $value) {
            $build .= $this->escape($key) . ' = ' . $this->escape($key) . ' - ' . $this->param($value) . ', ';
        }

        $build = substr($build, 0, -2);

        if ($wheres = $this->attr('wheres')) {
            $build .= $this->translateWhere($wheres);
        }

        if ($this->attr('limit')) {
            $build .= $this->translateLimit();
        }

        return $build;
    }

    /**
     * @throws Exception
     */
    protected function translateDelete(): string
    {
        $build = 'delete from ' . $this->escapeTable(false);

        if ($wheres = $this->attr('wheres')) {
            $build .= $this->translateWhere($wheres);
        }

        if ($this->attr('limit')) {
            $build .= $this->translateLimit();
        }

        return $build;
    }

    /**
     * @throws Exception
     */
    protected function translateSelect(): string
    {
        $build = ($this->attr('distinct') ? 'select distinct' : 'select') . ' ';

        $fields = $this->attr('fields');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                [$column, $alias] = $field;

                if (!is_null($alias)) {
                    $build .= $this->escape($column) . ' as ' . $this->escape($alias);
                } else {
                    $build .= $this->escape($column);
                }

                $build .= ', ';
            }

            $build = substr($build, 0, -2);
        } else {
            $build .= '*';
        }

        $build .= ' from ' . $this->escapeTable();

        if ($this->attr('joins')) {
            $build .= $this->translateJoins();
        }

        if ($wheres = $this->attr('wheres')) {
            $build .= $this->translateWhere($wheres);
        }

        if ($this->attr('groups')) {
            $build .= $this->translateGroupBy();
        }

        if ($this->attr('orders')) {
            $build .= $this->translateOrderBy();
        }

        if ($this->attr('limit') || $this->attr('offset')) {
            $build .= $this->translateLimitWithOffset();
        }

        return $build;
    }

    /**
     * @throws Exception
     */
    protected function translateWhere(array $wheres): string
    {
        $build = '';

        foreach ($wheres as $where) {
            if (!isset($where[2]) && isset($where[1]) && $where[1] instanceof BaseQuery) {
                $subAttributes = $where[1]->attributes();

                $build .= ' ' . $where[0] . ' ( ' . substr($this->translateWhere($subAttributes['wheres']), 7) . ' )';

                continue;
            }

            if (is_array($where[3])) {
                $where[3] = '(' . $this->parameterize($where[3]) . ')';
            } else {
                $where[3] = $this->param($where[3]);
            }

            $where[1] = $this->escape($where[1]);

            $build .= ' ' . implode(' ', $where);
        }

        return $build;
    }

    /**
     * @throws Exception
     */
    protected function translateJoins(): string
    {
        $build = '';

        foreach ($this->attr('joins') as $join) {
            [$type, $table] = $join;

            if (is_array($table)) {
                reset($table);

                if ($table[key($table)] instanceof Select) {
                    $translator = new static();

                    [$subQuery, $subQueryParameters] = $translator->translate($table[key($table)]);

                    // merge the parameters
                    foreach ($subQueryParameters as $parameter) {
                        $this->addParameter($parameter);
                    }

                    return '(' . $subQuery . ') as ' . $this->escape(key($table));
                }
            }

            $build .= ' ' . $type . ' join ' . $this->escape($table) . ' on ';

            if (!isset($join[3]) && isset($join[2]) && $join[2] instanceof BaseQuery) {
                $subAttributes = $join[2]->attributes();

                $joinConditions = '';

                // remove the first type from the ons
                reset($subAttributes['ons']);
                $subAttributes['ons'][key($subAttributes['ons'])][0] = '';

                foreach ($subAttributes['ons'] as $on) {
                    [$type, $localKey, $operator, $referenceKey] = $on;
                    $joinConditions .= ' ' . $type . ' ' . $this->escape(
                            $localKey
                        ) . ' ' . $operator . ' ' . $this->escape($referenceKey);
                }

                $build .= trim($joinConditions);

                if (!empty($subAttributes['wheres'])) {
                    $build .= ' and ' . substr($this->translateWhere($subAttributes['wheres']), 7);
                }
            } else {
                [, $table, $localKey, $operator, $referenceKey] = $join;
                $build .= $this->escape($localKey) . ' ' . $operator . ' ' . $this->escape($referenceKey);
            }
        }

        return $build;
    }

    /**
     * @throws Exception
     */
    protected function translateOrderBy(): string
    {
        $build = ' order by ';

        foreach ($this->attr('orders') as $column => $direction) {
            if (is_array($direction)) {
                [$column, $direction] = $direction;
            }

            $build .= $this->escape($column) . ' ' . $direction . ', ';
        }

        return substr($build, 0, -2);
    }

    /**
     * @throws Exception
     */
    protected function translateGroupBy(): string
    {
        return ' group by ' . $this->escapeList($this->attr('groups'));
    }

    protected function translateLimitWithOffset(): string
    {
        return ' limit ' . ((int)($this->attr('offset'))) . ', ' . ((int)($this->attr('limit')));
    }

    protected function translateLimit(): string
    {
        return ' limit ' . ((int)$this->attr('limit'));
    }

    /**
     * @throws Exception
     */
    protected function translateDrop(): string
    {
        return 'drop table ' . $this->escapeTable() . ';';
    }

    /**
     * @throws Exception
     */
    protected function translateTruncate(): string
    {
        return 'truncate table ' . $this->escapeTable() . ';';
    }

    /**
     * @throws Exception
     */
    protected function translateExists(): string
    {
        $translator = new static();

        [$subQuery, $subQueryParameters] = $translator->translate($this->attr('select'));

        foreach ($subQueryParameters as $parameter) {
            $this->addParameter($parameter);
        }

        return 'select exists(' . $subQuery . ') as `exists`';
    }
}
