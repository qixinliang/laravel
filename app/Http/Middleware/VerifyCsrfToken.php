<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
		'/merchant/register',
		'/merchant/login',
		'/merchant/logout',
		'/merchant/complete',
		'/merchant/lists',
		'/merchant/add',
		'/merchant/edit',
		'/merchant/del',
		'/merchant/info',
		'/merchant/erweima',
	
		'/sku/add',
		'/sku/edit',
		'/sku/del',
		'/sku/info',
		'/sku/lists',
		'/sku/audit',
        '/sku/get',

        'weixin/qr',
        'weixin/login',

        'coupon/set',
        'coupon/get',
    ];
}
