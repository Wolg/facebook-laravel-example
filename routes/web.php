<?php

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

Route::get('/', 'HomeController@index');
Route::get('/auth/facebook/callback', 'Auth\FacebookController@callback');
Route::get('/auth/facebook/logout', 'Auth\FacebookController@logout');
Route::get('/auth/facebook/deauthcallback', 'Auth\FacebookController@deAuthCallback');