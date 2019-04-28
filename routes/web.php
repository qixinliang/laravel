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
Route::post('/merchant/edit','MerchantController@edit');
Route::post('/merchant/del','MerchantController@del');
Route::post('/merchant/info','MerchantController@info');
Route::post('/merchant/erweima','MerchantController@erweima');

Route::post('/sku/add','SkuController@add');
Route::post('/sku/edit','SkuController@edit');
Route::post('/sku/del','SkuController@del');
Route::post('/sku/info','SkuController@info');
Route::post('/sku/audit','SkuController@audit');
Route::post('/sku/get','SkuController@getSkuByMid');

Route::post('/merchant/lists','MerchantController@lists');
Route::post('/sku/lists','SkuController@lists');

Route::get('/qiniu/token','QiniuController@getToken');
Route::get('/weixin/token','WeixinController@getAccessToken');

Route::post('/weixin/qr','WeixinController@miniprogramQr');
Route::post('/weixin/login','WeixinController@login');

Route::post('/coupon/set','CouponController@setCoupon');
Route::post('/coupon/get','CouponController@getCoupon');

Route::post('/ann/create','AnnouncementController@create');
Route::post('/ann/edit','AnnouncementController@edit');
Route::post('/ann/lists','AnnouncementController@lists');
Route::post('/ann/info','AnnouncementController@info');
Route::post('/ann/del','AnnouncementController@del');

Route::post('/promo/acquire','PromoController@acquire');
Route::post('/promo/my','PromoController@getPromoByopenid');
Route::post('/promo/consume','PromoController@consume');
Route::post('/promo/alls','PromoController@allPromosByMid');
Route::post('/promo/used','PromoController@allUsedpromosByMid');
