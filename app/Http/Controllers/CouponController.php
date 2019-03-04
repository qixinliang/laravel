<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Sku;
use App\Model\UserToken;
use App\Model\Coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class CouponController extends Controller{

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
            //先查是否在数据库里存在已经获得的优惠券
            $existed = Coupon::where(['openid' => $openid, 'merchant_id' => $mid,'sku_id' => $v])->first();
            if(!empty($existed)){
                continue;
            }
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

    public function getCoupon(Request $request){
        $params = $request->all();
        if(empty($params['data'])){
            return reponse()->json([
                'error_code' => -1,
                'error_msg' => '请求参数为空'
            ]);
        }

        $data = $params['data'];
        if(empty($data['openid'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '请求参数有误'
            ]);
        }

        $res = [];
        $openid = $data['openid'];
        $ret = Coupon::where(['openid' => $openid])->get();
        if(!empty($ret)){
            $ret = $ret->toArray(); 
            foreach($ret as $v){
                $sku = Sku::find($v['sku_id']); 
                if(empty($sku)){
                    continue;  
                }
                $sku = $sku->toArray();
                $temp = [
                    'openid' => $v['openid'],
                    'merchant_id' => $v['merchant_id'],
                    'sku_info' => $sku
                ];
                $res[] = $temp;
            }
        }
        return response()->json([
            'error_code' => 0,
            'error_msg' => '获取优惠券成功',
            'data' => $res
        ]);
    }
}
