<?php

declare(strict_types=1);

namespace Crys\Hydrahon;

use Crys\Hydrahon\Query\Sql;
use Crys\Hydrahon\Translator\Mysql;

/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
class Builder
{
    private const GRAMMARS = [
        'mysql' => [
            Sql::class,
            Mysql::class,
        ],
    ];

    protected BaseQuery $queryBuilder;
    protected TranslatorInterface $queryTranslator;

    /** @var callable */
    protected $executionCallback;

    /**
     * @throws Exception
     */
    public static function extend(string $grammarKey, string $queryBuilder, string $queryTranslator): void
    {
        if (isset(static::GRAMMARS[$grammarKey])) {
            throw new Exception('Cannot overwrite Hydrahon grammar.');
        }

        static::GRAMMARS[$grammarKey] = [$queryBuilder, $queryTranslator];
    }

    /**
     * @throws Exception
     */
    public function __construct(string $grammarKey, callable $executionCallback)
    {
        if (!isset(static::GRAMMARS[$grammarKey])) {
            throw new Exception('There is no Hydrahon grammar "' . $grammarKey . '" registered.');
        }

        if (!is_callable($executionCallback)) {
            throw new Exception('Invalid query exec callback given.');
        }

        $this->executionCallback = $executionCallback;

        [$queryBuilderClass, $translatorClass] = static::GRAMMARS[$grammarKey];

        $this->queryTranslator = new $translatorClass();
        $this->queryBuilder    = new $queryBuilderClass();

        $this->queryBuilder->setResultFetcher(array($this, 'executeQuery'));

        if (!$this->queryTranslator instanceof TranslatorInterface) {
            throw new Exception('A query translator must implement the "TranslatorInterface" interface.');
        }

        if (!$this->queryBuilder instanceof BaseQuery) {
            throw new Exception('A query builder must be an instance of the "BaseQuery".');
        }
    }

    public function __call(string $method, array $arguments): BaseQuery
    {
        return call_user_func_array([$this->queryBuilder, $method], $arguments);
    }

    public function translateQuery(BaseQuery $query): array
    {
        return $this->queryTranslator->translate($query);
    }

    public function executeQuery(BaseQuery $query): mixed
    {
        return call_user_func_array(
            $this->executionCallback,
            array_merge([$query], $this->translateQuery($query))
        );
    }
}
