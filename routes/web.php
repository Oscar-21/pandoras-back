<?php
use App\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
  \Debugbar::startMeasure('query_time', 'The execution time of user query');
  $users = User::get();
  \Debugbar::stopMeasure('query_time');
  return view('welcome');
});
Route::any('{path?}', 'MainController@index')->where("path", ".+");
