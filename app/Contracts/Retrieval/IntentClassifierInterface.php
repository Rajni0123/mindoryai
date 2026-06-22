<?php

namespace App\Contracts\Retrieval;

use App\Services\Retrieval\DTO\IntentResult;
use App\Services\Retrieval\DTO\RetrievalQuery;

interface IntentClassifierInterface
{
    public function classify(RetrievalQuery $query): IntentResult;
}
