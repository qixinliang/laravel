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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hi',function(){
	return 'hello world';
});

Route::get('/channels','ChannelController@index');
Route::get('/login','ChannelController@login');
Route::post('/account','ChannelController@account');

Route::get('/collect','HouseController@collect');


Route::post('/merchant/register','MerchantController@register');
Route::post('/merchant/login','MerchantController@login');
Route::post('/merchant/logout','MerchantController@logout');
Route::post('/merchant/complete','MerchantController@complete');
Route::post('/merchant/add','MerchantController@add');

Route::get('/merchant/lists','MerchantController@lists');
