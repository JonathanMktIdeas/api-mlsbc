<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Models\User;
use App\Models\Member;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next, $guard = 'member')
     {
         $token = $request->header('token');
         if(!$token) {
             // Unauthorized response if token not there
             return response()->json([
                 'error' => 'Token not provided.'
             ], 401);
         }
         try {
             $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);

             if($guard !== $credentials->iss)
             {
                 return response()->json([
                     'data' => null,
                     'message' => 'No access',
                 ], 401);
             }

         } catch(ExpiredException $e) {
             return response()->json([
                 'error' => 'Provided token is expired.'
             ], 400);
         } catch(Exception $e) {
             return response()->json([
                 'error' => 'An error while decoding token.'
             ], 400);
         }

         $user = null;

         if ($credentials->iss == 'admin')
         {
             $user = User::find($credentials->sub);
             $request->userType = 'admin';
         }
         else if ($credentials->iss == 'member')
         {
             $user = Member::find($credentials->sub);
             $request->userType = 'member';
         }

         // if (!$user->status) {
         //     return response()->json([
         //         'error' => 'User bloqued'
         //     ], 401);
         // }
         // Now let's put the user in the request class so that you can grab it from there
         $request->auth = $user;
         return $next($request);
     }
}
