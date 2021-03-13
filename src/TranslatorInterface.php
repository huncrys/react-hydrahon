<?php

declare(strict_types=1);

namespace Crys\Hydrahon;

interface TranslatorInterface
{
    public function translate(BaseQuery $query): array;
}
