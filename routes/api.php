<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Roles Routes
Route::post('storeRole', 'RolesController@store');
Route::get('getRoles', 'RolesController@index');
Route::post('updateRole/{id}', 'RolesController@update');
Route::get('showRole/{id}', 'RolesController@show');
Route::post('deleteRole/{id}', 'RolesController@destroy');

// User Routes
Route::post('signUp', 'UsersController@signUp');
Route::post('signIn', 'UsersController@signIn');
Route::get( 'index', 'UsersController@index'); 
Route::get('isSub/{id}', 'UsersController@isUserSubscribed');

//Redirect invalid requests
Route::any('{path?}', 'MainController@index')->where("path", ".+");

