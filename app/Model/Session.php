<?php 

namespace App\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;

class Session extends Model{

	public $timestamps = false;

	protected $table = 'sessions';

	const TOKEN_PREFIX = 'token:';

	public $token       = '';
    public $uid         = 0;
	public $platform    = '';
    public $createTime  = 0;

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
            $session = new Session();
            $session->token = $token;
            foreach($arr as $k => $v){
                $session->$k = $v;
            }
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
