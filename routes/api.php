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

//*** User Routes *****//
Route::get('index', 'UsersController@index'); // USER Show info
Route::post('signUp', 'UsersController@signUp'); // USER/ADMIN: Sign up/ Subscribe
Route::post('signIn', 'UsersController@signIn'); // USER: Login
Route::get( 'showUsers', 'UsersController@indexUsers');  // ADMIN: Index users
Route::get('isSub/{id}', 'UsersController@isUserSubscribed'); // ADMIN: check if user is subscribed
Route::get('inv', 'UsersController@invoicePDF'); // USER: Generate PDF

Route::get('out', function() {
    return Response::json(Auth::check());
}); // USER: Generate PDF

Route::get('cancel', 'UsersController@userCancelNow'); // User: cancel subscription immediately 
Route::get('getPostageKey', function() {
  return Response::json(config('services.postal.username'));
});
Route::post('xml', 'UsersController@xml');

Route::get( 'check', 'UsersController@checkLog');  // ADMIN: Index users

Route::get( 'lout', 'UsersController@LogOut');  // ADMIN: Index users

Route::get('wtf', function() {
  return Response::json('wtf');
});

// Test routes
Route::get('try', 'UsersController@tryMe');
Route::get('tryMe/{id}', 'UsersController@tryMe');
Route::get('testDebug', function() {
  $j = 0;
  for ($i = 0; $i < 10; $i++)
  {
    $j++;
  }
  return Response::json($j);
});


// Contact Routes
Route::get('message', 'ContactsController@message');
Route::post('updateMessage', 'ContactsController@updateMessage');

//Redirect invalid requests
Route::any('{path?}', 'MainController@index')->where("path", ".+");

