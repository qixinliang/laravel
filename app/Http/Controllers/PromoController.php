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
    
    public function acquire(Request $request){
        require_once __DIR__ . '/../../../vendor/phpqrcode/phpqrcode.php';
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
                    "openid":"aaa",
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
            $skuId  = $v['sku_id'];
            $number = $v['number'];
            
            $skuObj = Sku::find($skuId);
            if(empty($skuObj)) continue;
            if($skuObj->is_delete == 1) continue;

            for($i = 0; $i < $number; $i++){
                $promo  = new Promo();
                $code   = Promo::createNo();
                $time   = date("Y-m-d H:i:s");
                $promo->sku_id              = $skuId;
                $promo->promo_code          = $code;
                $promo->promo_display_code  = md5($code.'_'.$openid);
                $promo->period_start        = $time;
                $promo->period_end          = date("Y-m-d H:i:s",strtotime($time)+$skuObj->valid_time * 24 *3600);
                $promo->openid              = $openid;
                $promo->pre_openid          = 0;
                $promo->add_time            = $time;
                $promo->promo_status        = Promo::STATUS_NORMAL;
                $promo->promo_type          = 1; //暂定1
                $promo->obj_src             = Promo::BY_OFFICAL_GAME;


                $value = $promo->promo_display_code; //二维码内容
                $errorCorrectionLevel   = 'L'; //容错级别
                $matrixPointSize        = 5;   //生成图片大小
                $basepath = '/qrcode/'.$value.'.png';
                $filename = $_SERVER['DOCUMENT_ROOT'] . $basepath;//生成二维码图
                file_put_contents($filename,'');
                $final = $request->server()['HTTP_HOST'] . $basepath;

                \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
                $QR = $filename; //已经生成的原始二维码图片文件
                $QR = imagecreatefromstring(file_get_contents($QR));

                imagepng($QR, 'qrcode.png');//输出图片
                imagedestroy($QR);

                $promo->erweima = $final;

                if(!$promo->save()){
                    continue;
                }
            }
        }
        return response()->json([
            'error_code' => 0, 
            'error_msg'  => '获得优惠券成功'
        ]);
    }


    //需加分页
    public function getPromoByopenid(Request $request){
        $params = $request->all();
        if(empty($params['data']['openid'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => 'openid参数有误'
            ]);
        }
        $openid = $params['data']['openid'];
        $promos = Promo::where(['openid'=>$openid,'promo_status' => Promo::STATUS_NORMAL])->get();

        return response()->json([
            'error_code' => 0, 
            'error_msg'  => 'success',
            'data' => $promos
        ]);
    }

    public function consume(Request $request){
        $params = $request->all();
        if(empty($params['data'])){
            return response()->json([
                'error_code' => -1,    
                'error_msg' => '请求参数有误'
            ]); 
        }
        $data = $params['data'];
        if(empty($data['promo_display_code'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '未传入核销券号'
            ]); 
        }
        $code = $data['promo_display_code'];
        $promo = Promo::where(['promo_display_code' => $code,'promo_status' => Promo::STATUS_NORMAL])->first();
        if(empty($promo)){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '券不存在或已消费'
            ]);
        }
        if(time() > strtotime($promo->period_end)){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '优惠券已过期，无法使用'
            ]);
        }
        $promo->promo_status = Promo::STATUS_USED;
        $promo->save();
        return response()->json([
            'error_code' => 0, 
            'error_msg'  => '消费成功'
        ]);
    }


    //在系统里弄一个表openid nickname uid的用户表
    public function donate(Request $request){
        $params = $request->all();
        if(empty($params['data'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg'  => '参数错误'
            ]);
        }
    }
}
