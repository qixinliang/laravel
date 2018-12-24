<?php
namespace App\Observers;

use App\Model\UserToken;
use Illuminate\Support\Facades\Redis;

class TokenObserver{
	public function __construct(){
		//dump("Observer construct");
	}

/*
	public function saving(UserToken $session){
		dump("saving");
		$curTime = time();
        if(empty($session->token)){
			dump($session->token);
            $session->token = md5($session->uid . '|' . $session->platform . '|' . $curTime . '|' . Session::generateCode(12));
			dump($session->token);
        }
        $session->create_time = $curTime;
	}*/

	public function saved(UserToken $session){
		//dump("saved");
		//dump($session);
		$expireTime = $session->create_time + (86400 * 90);
        $key = UserToken::TOKEN_PREFIX . $session->token;
		//dump($key);

        $data = [
            'uid'       => $session->uid,
            'platform' => $session->platform,
            'create_time'   => $session->create_time
        ];
		//dump($data);
        Redis::hmset($key, $data);
        Redis::expireAt($key, $expireTime);
	}
}
