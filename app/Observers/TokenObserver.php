<?php
namespace App\Observers;

use App\Model\Token;
use Illuminate\Support\Facades\Redis;

class TokenObserver{
	public function __construct(){
		$b = "observer construct";
		dump($b);
	}

/*
	public function saving(Session $session){
		dump("saving");
		$curTime = time();
        if(empty($session->token)){
			dump($session->token);
            $session->token = md5($session->uid . '|' . $session->platform . '|' . $curTime . '|' . Session::generateCode(12));
			dump($session->token);
        }
        $session->create_time = $curTime;
	}

	public function saved(Session $session){
		dump("saved");
		dump($session);
		$expireTime = $session->create_time + (86400 * 90);
        $key = Session::TOKEN_PREFIX . $session->token;

        $data = [
            'uid'       => $session->uid,
            'platform' => $session->platform,
            'create_time'   => $session->create_time
        ];
		dump($data);
        Redis::hmset($key, $data);
        Redis::expireAt($key, $expireTime);
	}
	*/
}
