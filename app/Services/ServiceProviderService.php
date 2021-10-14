<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\Agency;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use Exception;
use DB;

class ServiceProviderService extends BaseService
{
    private $request;
    public function __construct(ServiceProvider $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
    }
}
