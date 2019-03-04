<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;
use App\Model\Coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class CouponController extends Controller{

    //POST
    public function setCoupon(Request $request){
        $params = $request->all();
        if(empty($params['data'])){
            return reponse()->json([
                'error_code' => -1,
                'error_msg' => '请求参数为空'
            ]);
        }

        $data = $params['data'];
        if(empty($data['openid']) || empty($data['mchId']) || empty($data['couponList'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '请求参数有误'
            ]);
        }

        $openid = $data['openid'];
        $mid = $data['mchId'];
        $couponList = $data['couponList'];

        foreach($couponList as $k => $v){
            $coupon = new Coupon(); 
            $coupon->openid = $openid;
            $coupon->merchant_id = $mid;
            $coupon->sku_id = $v;
            $coupon->create_time = date("Y-m-d H:i:s");
            if(!$coupon->save()){
                continue; 
            }
        }

        return response()->json([
            'error_code' => 0,
            'error_msg' => '保存优惠券成功'
        ]);
    }
}
