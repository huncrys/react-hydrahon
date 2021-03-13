<?php

declare(strict_types=1);

namespace Crys\Hydrahon;

use BadMethodCallException;
use Crys\Hydrahon\Query\Expression;
use JetBrains\PhpStorm\Pure;

class BaseQuery
{
    protected array $macros = [];
    protected array $flags = [];

    /** @var callable|null */
    protected $resultFetcher;

    final public function __construct(BaseQuery $parent = null)
    {
        if (!is_null($parent)) {
            $this->inheritFromParent($parent);
        }
    }

    protected function inheritFromParent(BaseQuery $parent): void
    {
        $this->macros        = $parent->macros;
        $this->flags         = $parent->flags;
        $this->resultFetcher = $parent->resultFetcher;
    }

    public function setResultFetcher(callable $resultFetcher): void
    {
        $this->resultFetcher = $resultFetcher;
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

        call_user_func_array($callback, array(&$this));

        return $this;
    }

    #[Pure] final public function raw(string $expression): Expression
    {
        return new Expression($expression);
    }

    final public function attributes(): array
    {
        $excluded   = ['resultFetcher', 'macros'];
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

    /**
     * @throws Exception
     */
    final protected function executeResultFetcher(): mixed
    {
        if (is_null($this->resultFetcher)) {
            throw new Exception('Cannot execute result fetcher callbacks without inital assignment.');
        }

        return call_user_func_array($this->resultFetcher, [&$this]);
    }

    /**
     * @throws Exception
     */
    public function execute(): mixed
    {
        return $this->executeResultFetcher();
    }
}
