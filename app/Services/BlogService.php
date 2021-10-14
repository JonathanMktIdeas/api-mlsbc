<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Agency;
use App\Services\OpenPayService;
use Illuminate\Support\Facades\Hash;
use Exception;
use DB;

class ResourceLibraryService extends BaseService
{
    private $request;
    public function __construct(Blog $model, Request $request)
    {
        $this->model = $model;
        $this->request = $request;
    }
}
