<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResourceLibraryService;
use Exception;

class ResourceLibraryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ResourceLibraryService $service, Request $request)
    {
        parent::__construct($service, $request);
    }


}
