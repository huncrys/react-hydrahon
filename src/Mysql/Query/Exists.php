<?php

declare(strict_types=1);

namespace Crys\Hydrahon\Mysql\Query;

use Crys\Hydrahon\BaseQuery;

class Exists extends BaseQuery implements FetchableInterface
{
    protected ?Select $select = null;

    public function setSelect(Select $select): void
    {
        $this->select = $select;
    }
}
