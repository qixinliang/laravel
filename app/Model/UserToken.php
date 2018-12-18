<?php 

namespace App\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model{


	protected	$table			= 'user_token';
	protected	$primaryKey		= 'token';   //自定义主键字段
	protected	$keyType		= 'string';  //自定义主键类型为字串
	public		$incrementing	= false;     //主键非自增型
	public		$timestamps		= false;
	//protected   $guarded		= [];
	//protected   $fillable = ['token','uid','platform','create_time'];

	const TOKEN_PREFIX = 'token:';

	public static function generateCode($length = 4) {
		return rand(pow(10,($length-1)), pow(10,$length)-1);
    }


    /**
     * 根据 token 获取会话信息
     * @param $token
     * @return mixed
     */
    public static function getByToken($token){

        $session = null;
        $key = self::TOKEN_PREFIX . $token;
        
        $arr = Redis::hgetall($key);
        if($arr){
            $session = new Usertoken();
            $session->token = $token;
            foreach($arr as $k => $v){
                $session->$k = $v;
            }
			//无$session->save()，不写入库中，只是从缓存里获取了array，构建session的object返回给上层
            return $session;
        }
		$session = self::where('token', $token)->first();
        if($session){
            Redis::hmset($key, ['uid' => $session->uid, 'platform' => $session->platform]);
            Redis::expireAt($key, 90 * 86400); //过期时间90天
        }
        return $session;
    }
}
