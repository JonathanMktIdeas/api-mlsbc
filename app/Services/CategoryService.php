<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Agency;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use Exception;
use DB;

class CategoryService extends BaseService
{
    private $request;
    public function __construct(Category $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
    }
}
