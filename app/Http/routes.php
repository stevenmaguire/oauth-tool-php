<?php



/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$app->get('/', ['as' => 'start', 'uses' => 'AuthController@getAuthOptions']);
$app->post('/', ['as' => 'auth.redirect', 'uses' => 'AuthController@postAuth']);
$app->get('{protocol:oauth1|oauth2}/{provider}', ['as' => 'auth', 'uses' => 'AuthController@getAuth']);
