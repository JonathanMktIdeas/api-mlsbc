<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next, $guard = null)
     {
        $apiKey = $request->header('api-key');
        $correct = true;

        if (!$apiKey) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'Api Key not provided.'
            ], 400);
        }

        if ($apiKey == env('API_KEY_ADMIN'))
        {
            $request->src = 'admin';
            if (!is_null($guard) && $guard != 'admin')
            {
                $correct = false;
            }
        }
        else if ($apiKey == env('API_KEY_WEB'))
        {
            $request->src = 'web';
            if (!is_null($guard) && $guard != 'web')
            {
                $correct = false;
            }
        }
        else
        {
            return response()->json([
                'error' => 'Api key incorrect'
            ], 400);
        }

        if (!$correct) {
            return response()->json([
                'error' => 'Api key cannot access this endpoint'
            ], 400);
        }

        return $next($request);
     }
}
