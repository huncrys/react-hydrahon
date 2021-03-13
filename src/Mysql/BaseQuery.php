<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql;

use BadMethodCallException;
use Crys\Hydrahon\Exception;
use Crys\Hydrahon\Mysql\Expression;
use Crys\Hydrahon\Mysql\Query\Delete;
use Crys\Hydrahon\Mysql\Query\FetchableInterface;
use Crys\Hydrahon\Mysql\Query\Insert;
use Crys\Hydrahon\Mysql\Query\Replace;
use Crys\Hydrahon\Mysql\Query\Update;
use Crys\Hydrahon\TranslatorInterface;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;

class BaseQuery
{
    protected array $macros = [];
    protected array $flags = [];

    final public function __construct(
        protected ConnectionInterface $connection,
        protected TranslatorInterface $translator,
        ?BaseQuery $parent = null
    ) {
        if (!is_null($parent)) {
            $this->inheritFromParent($parent);
        }
    }

    protected function inheritFromParent(BaseQuery $parent): void
    {
        $this->macros = $parent->macros;
        $this->flags  = $parent->flags;
    }

    final public function setFlag(string $key, mixed $value): void
    {
        $this->flags[$key] = $value;
    }

    final public function getFlag(string $key, mixed $default = null): mixed
    {
        return $this->flags[$key] ?? $default;
    }

    final public function macro(string $method, callable $callback): void
    {
        $this->macros[$method] = $callback;
    }

    /**
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments): static
    {
        if (!isset($this->macros[$name])) {
            throw new BadMethodCallException('There is no macro or method with the name "' . $name . '" registered.');
        }

        call_user_func_array($this->macros[$name], array_merge([&$this], $arguments));

        return $this;
    }

    /**
     * @throws Exception
     */
    public function call(callable $callback): static
    {
        if (!is_callable($callback)) {
            throw new Exception('Invalid query callback given.');
        }

        call_user_func_array($callback, [&$this]);

        return $this;
    }

    final public function raw(string $expression): Expression
    {
        return new Expression($expression);
    }

    final public function attributes(): array
    {
        $excluded   = ['macros'];
        $attributes = get_object_vars($this);

        foreach ($excluded as $key) {
            if (isset($attributes[$key])) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    final public function overwriteAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $attribute) {
            if (isset($this->{$key})) {
                $this->{$key} = $attribute;
            }
        }

        return $attributes;
    }

    final protected function run(): PromiseInterface
    {
        return $this->connection->query(...$this->translator->translate($this))
            ->then(
                function (QueryResult $result): array|int|null {
                    if ($this instanceof FetchableInterface) {
                        return $result->resultRows;
                    }

                    if ($this instanceof Insert) {
                        return $result->insertId;
                    }

                    if ($this instanceof Update || $this instanceof Replace || $this instanceof Delete) {
                        return $result->affectedRows;
                    }

                    return null;
                }
            );
    }

    public function execute(): PromiseInterface
    {
        return $this->run();
    }
}
