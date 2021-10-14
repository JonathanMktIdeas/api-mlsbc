<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\BlogCategory;
use App\Models\Agency;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use Exception;
use DB;

class BlogCategoryService extends BaseService
{
    private $request;
    public function __construct(BlogCategory $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
    }
}
