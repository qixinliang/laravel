<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Sku;
use App\Model\UserToken;
use App\Model\Promo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class PromoController extends Controller{
    
    //FIXME 此处有没刷接口的风险?
    public function acquireAction(Request $request){
        $params = $request->all();
        if(empty($params['data'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '请求参数为空'
            ]);
        }

        $data = $params['data'];

        /*
          {
                "data":{
                    "openid":"xxx",
                    "sku_info":[
                        {
                            "sku_id":1,
                            "number":10
                        },
                        {
                            "sku_id":2,
                            "number":20
                        }
                    ]
                }
            }
         */
        if(empty($data['openid'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg'  => '未传入openid参数'
            ]);
        }

        if(empty($data['sku_info'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '并未获得优惠券，再接再厉'
            ]);
        }

        $openid = $data['openid'];
        $skuInfo = $data['sku_info'];

        foreach($skuInfo as $v){
            foreach($v as $kk => $vv){
                $skuId = $v['sku_id'];
                $number = $v['number'];
            }
        }
        return response()->json([
            'error_code' => 0, 
            'error_msg'  => '获得优惠券成功'
        ]);
    }
}
