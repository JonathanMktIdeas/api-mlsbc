<?php

namespace App\Services;

use App\Models\BaseModel;

class BaseService
{
    protected $model;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }

    function getPaymentInfo() { }
}
