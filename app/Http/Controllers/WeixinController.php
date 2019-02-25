<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class WeixinController extends Controller{
    public $access_token_cache_key = "weixin_access_token";
    public $expires_time = 7000;
    private  $appId = "wx26789cab38fb02b9";
    private $appSecret = "ba78afcf1cd7c5655693016e42ff3c78";
    public function getAccessToken(){
        $access_token = Redis::get($this->access_token_cache_key);
        if (empty($access_token)) {
            // 如果是企业号用以下URL获取access_token
            //$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->httpGet($url);
            $res = json_decode($res);
            $access_token = $res->access_token;
            if ($access_token) {
                Redis::setEx($this->access_token_cache_key, $this->expires_time, $access_token);
            }
        }
	    return response()->json([
			'error_code' => 0,
			'error_msg' => '获取token成功',
            'data' => $access_token
		]);
    }

	public function miniprogramQr(Request $request){
		$params = $request->all();
		if(empty($params['uid']) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$loginUid = $params['uid'];

		$platform = 0;
		$tokenData = UserToken::where(['uid' => $loginUid, 'platform' => $platform])->first();
		if(empty($tokenData)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请先登陆'
			]);
		}
		$accessToken = isset($params['access_token'])? $params['access_token'] : 0;
		if($tokenData->token != $accessToken){
			return resonse()->json([
				'error_code' => -1,
				'error_msg'  => '数据异常，token不一致'
			]);
		}

		$data = $params['data'];
		if(empty($data['merchant_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '商户id为空'
			]);
		}
		$mid = $data['merchant_id'];
		$row = Merchant::find($mid);
		if(empty($row)){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '商户数据为空'
			]);
		}
		if($row->id != $loginUid && $row->creator_uid != $loginUid){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '仅允许生成自己或者自己创建的商户的二维码'
			]);	
		}

        //post提交

        $access_token = Redis::get($this->access_token_cache_key);
        if (empty($access_token)) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->httpGet($url);
            $res = json_decode($res);
            $access_token = $res->access_token;
            if ($access_token) {
                Redis::setEx($this->access_token_cache_key, $this->expires_time, $access_token);
            }
        }

        //data
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$access_token}";

        $arr = [
            "path" => "merchant_id={$mid}",
            "width" => 430,
            "auto_color" => false,
            "line_color" => [
                "r" => 0,
                "g" => 0,
                "b" => 0
            ]
        ];

        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";

        $arr = [
            "scene" => $mid,
            "width" => 430,
            "auto_color" => false,
            "line_color" => [
                "r" => 0,
                "g" => 0,
                "b" => 0
            ]
        ];

        $data_string =  json_encode($arr);
        var_dump($data_string);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string)
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errno = curl_errno($curl);
        curl_close($curl);
		$row->save();

        $result=$this->data_uri($res,'image/png');
         return '<image src='.$result.'></image>';

        /*
		return response()->json([
			'error_code' => 0,
			'error_msg' => '生成小程序码成功',
			'data' => [
				'mini_pic' => $result,
			]
		]);*/
	}

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    //二进制转图片image/png
    public function data_uri($contents, $mime)
    {
        $base64   = base64_encode($contents);
        return ('data:' . $mime . ';base64,' . $base64);
    }
}
