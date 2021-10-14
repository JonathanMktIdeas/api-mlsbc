<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ServiceProviderService;
use Exception;

class ServiceProviderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ServiceProviderService $service, Request $request)
    {
        parent::__construct($service, $request);
    }


}
