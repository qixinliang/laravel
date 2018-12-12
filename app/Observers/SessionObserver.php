<?php
namespace App\Observers;

use App\Model\Session;
use Illuminate\Support\Facades\Redis;

class SessionObserver{
	public function creating(Session $session){
		$curTime = time();
        if(empty($session->token)){
            $session->token = md5($session->uid . '|' . $session->platform . '|' . $curTime . '|' . Session::generateCode(12));
        }
        $session->createTime = $curTime;
	}
	public function saved(Session $session){
		$expireTime = $session->createTime + (86400 * 90);
        $key = Session::TOKEN_PREFIX . $session->token;

        $data = [
            'uid'       => $session->uid,
            'platform' => $session->platform,
            'create_time'   => $session->createTime
        ];
        Redis::hmset($key, $data);
        Redis::expireAt($key, $expireTime);
	}
}
