<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Services\BaseService;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    protected $request;
    protected $service;

    public function __construct(BaseService $service, Request $request)
    {
        $this->request = $request;
        $this->service = $service;
    }
}
