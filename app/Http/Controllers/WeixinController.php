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
}
