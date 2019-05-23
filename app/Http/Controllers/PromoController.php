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

use App\Library\RedLock\RedLock;

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

        $redisConfig = config('cache.stores.redis');
        if(empty($redisConfig)){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '未配置redis缓存'
            ]); 
        }

        $servers = [
			[$redisConfig['host'], $redisConfig['port'], 0.01]
		];

        $redLock = new RedLock($servers);
        $lock = false;
        while(!$lock){
            $lock = $redLock->lock("openid_{$openid}",1000);
        }

        try{
            DB::beginTransaction();
            foreach($skuInfo as $v){
                $skuId  = $v['sku_id'];
                $number = $v['number'];
                
                $skuObj = Sku::find($skuId);
                if(empty($skuObj)) continue;
                if($skuObj->is_delete == 1) continue;

                if($skuObj->cnt != 0 && $number > $skuObj->cnt){
                    continue;
                }

                $merchantId = $skuObj->creator_uid;
                $existedPromos = Promo::where(['sku_id' => $skuId,'merchant_id' => $merchantId, 'promo_status' => Promo::STATUS_NORMAL])->count();

                if($skuObj->cnt != 0 && $existedPromos == $skuObj->cnt){
                    throw new \Exception("该优惠券已发放完，不能再领取");
                }

                for($i = 0; $i < $number; $i++){
                    $promo  = new Promo();
                    $code   = Promo::createNo();
                    $time   = date("Y-m-d H:i:s");
                    $promo->sku_id              = $skuId;
                    $promo->merchant_id         = $merchantId;
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
            DB::commit();
            return response()->json([
                'error_code' => 0, 
                'error_msg'  => '获得优惠券成功'
            ]);
        }catch(\Exception $e){
            DB::rollBack();
            return $this->response()->json([
                'error_code' => -1,
                'error_msg' => '错误，出现异常'
            ]);
        }
        finally{
            $redLock->unlock($lock);
        }
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
		$promos = DB::table('promo')
				    ->join('sku','sku.id','=','promo.sku_id')
				    ->select('promo.*','sku.sku_name')
                    ->where('promo.openid', '=', $openid)
				    ->where('promo.promo_status','=',Promo::STATUS_NORMAL)
					->orderBy('promo.id','desc')
				    ->get();

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

    public function allPromosByMid(Request $request){
        $params = $request->all();
        if(empty($params['data']['merchant_id'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '参数错误'
            ]);
        }
        
        $mid = $params['data']['merchant_id'];

        $res = DB::table('promo')
               ->join('sku','sku.id','=','promo.sku_id')
               ->select(DB::raw('promo.sku_id, count(*) as number,sku.sku_name,sku.creator_name'))
               ->where('promo.merchant_id', '=',$mid)
               ->where('promo.promo_status','=',Promo::STATUS_NORMAL)
               ->groupBy('promo.sku_id')
               ->get();
        return response()->json([
            'error_code' => 0, 
            'error_msg' => '获取统计结果成功',
            'data' => $res
        ]);
    }

    public function allUsedpromosByMid(Request $request){
        $params = $request->all();
        if(empty($params['data']['merchant_id'])){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '参数错误'
            ]);
        }
        
        $mid = $params['data']['merchant_id'];

        $res = DB::table('promo')
               ->join('sku','sku.id','=','promo.sku_id')
               ->select(DB::raw('promo.sku_id, count(*) as number,sku.sku_name,sku.creator_name'))
               ->where('promo.merchant_id', '=',$mid)
               ->where('promo.promo_status','=',Promo::STATUS_USED)
               ->groupBy('promo.sku_id')
               ->get();
        return response()->json([
            'error_code' => 0, 
            'error_msg' => '获取统计结果成功',
            'data' => $res
        ]);
    }

public function test(Request $request){
        $params = $request->all();
        var_dump($params);

        if(empty($params['code'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => 'get weixin code error'
            ]);
        }

        $code = $params['code'];
        $appid = 'wx0ae56cd6f90bc2d7';
        $secret = '4f49025ea331023bf4f6d3ad9fec67a1';
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_token_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
               $res = curl_exec($ch);
        curl_close($ch);
        $json_obj = json_decode($res,true);
        $access_token = $json_obj['access_token'];
        $openid = $json_obj['openid'];

        var_dump($openid);
        $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_user_info_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $res = curl_exec($ch);

        curl_close($ch);

        $user_obj = json_decode($res,true);

        $_SESSION['user'] = $user_obj;

        $tmp_array = ['oi4J51AmO7GWRffewlvnBNpegHeQ','oi4J51LfHAafx4IoXiZznH22QsEQ'];
        if(!in_array($openid,$tmp_array)){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => 'not hexiaoyuan, bu neng he xiao '
            ]); 
        }
               return response()->json([
            'error_code' => 0,
            'error_msg' => 'consume test',
            'data' => $user_obj
        ]);
    }
}
