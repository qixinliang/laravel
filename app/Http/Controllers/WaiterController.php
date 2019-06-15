<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class WaiterController extends Controller{
    private function getUserinfo($code){
        $appid  = 'wx0ae56cd6f90bc2d7';
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
        var_dump($user_obj);

        return $user_obj;
    }

    public function bind(Request $request){
        header('content-type:application/json;charset=utf8');
        $params = $request->all();
        if(empty($params['merchant_id'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '商家id参数传递有误'
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        
        }

        if(empty($params['code'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '微信code参数传递有误'
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        $code   = $params['code'];
        $mid    = $params['merchant_id'];

        $userinfo = $this->getUserinfo($code);
        exit;
        return response()->json([
            'error_code' => 0,
            'error_msg' => '核销成功',
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }
}
