<?php

declare(strict_types=1);

namespace Crys\Hydrahon;

use Crys\Hydrahon\Mysql\BaseQuery;

interface TranslatorInterface
{
    public function translate(BaseQuery $query): array;
}
