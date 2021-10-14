<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BlogCategoryController;
use Exception;

class BlogCategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CategoryController $service, Request $request)
    {
        parent::__construct($service, $request);
    }


}
