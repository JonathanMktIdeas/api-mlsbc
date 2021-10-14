<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('sync', 'MemberController@sync');
    $router->get('rememberLogin', 'MemberController@rememberLogin');
    $router->get('board', 'MemberController@board');

    $router->post('newsletter', 'MemberController@newsletter');
    $router->post('form', 'MemberController@subscribe');
    // member routes
    $router->group(['prefix' => 'member', 'middleware' => 'api.key:web'], function () use ($router) {
        $router->group(['prefix' => 'auth'], function () use ($router) {
            $router->post('recover', 'MemberController@recover');
            $router->post('login', 'MemberController@login');
            $router->post('setPasswords', 'MemberController@setPasswords');
        });

        $router->get('kya', 'MemberController@kya');
        $router->get('paginated', 'MemberController@paginated');
        $router->group(['middleware' => 'jwt.auth:member'], function () use ($router) {
            $router->post('updatePassword', 'MemberController@updatePassword');
            $router->get('payment-info', 'MemberController@init');
            $router->get('list', 'MemberController@list');
            $router->get('me',   'MemberController@me');
            $router->post('pay', 'MemberController@pay');
            $router->post('pay/confirm', 'MemberController@confirmPay');
            $router->post('url', 'MemberController@url');
            $router->post('confirm', 'MemberController@confirm');
        });
    });

    $router->group(['prefix' => 'events', 'middleware' => 'api.key:web'], function () use ($router) {
        $router->group(['middleware' => 'jwt.auth:member'], function () use ($router) {
            $router->post('/',       'EventController@create');
            $router->get('/',        'EventController@list');
            $router->delete('/{id}', 'EventController@deleteEvent');
            $router->put('/{id}',    'EventController@updateEvent');
        });
    });
});
