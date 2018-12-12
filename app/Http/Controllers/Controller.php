<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Session;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	public $controller;
	public $method;

	protected $uid;
	protected $accessToken;
	protected $testAccessToken;

	protected $tokenCheckPassed = false;


	protected static $notNeedVerifyToken = [
		'MerchantController' => 1,
	];

	protected static $notNeedVerifyTokenByAct = [
		'MerchantController/lists' => 1,
	];

	public function __construct(Request $request){
		$params = $request->all();
		if(empty($params)){
			return reponse()->json([
				'error_code' => -1,
				'error_msg' => '请求参数为空'
			]);
		}
		$this->uid = isset($params['uid'])? intval($params['uid']) : '';
		$this->accessToken = isset($params['access_token'])? $params['access_token'] : '';
		$this->testAccessToken = isset($params['test_access_token'])? $params['test_access_token'] : '';

		$this->controller = $this->getCurrentControllerName();
		$this->method = $this->getCurrentMethodName();
		var_dump($this->controller);
		var_dump($this->method);

		if (!isset(self::$notNeedVerifyToken[$this->controller]) && !isset(self::$notNeedVerifyTokenByAct[$this->controller."/".$this->action])) {
        	$this->checkToken();
            if ($this->tokenCheckPassed == false) {
                $this->tokenFailOut();
                exit;
            }
        }

	}

	protected function tokenFailOut(){
		return response()->json([
			'error_code' => -1,
			'error_msg' => 'check access_token failed'
		]);
	}

	protected function checkToken(){
		if($this->testAccessToken == 'situxu001'){
			$this->tokenCheckPassed = true;
		}else{
			$session = new Session();
            $row = $session->getByToken($this->accessToken);
            //校验是否为黑名单用户
            if (empty($row->uid)){
                $this->tokenCheckPassed = false;
                return;
            }
            if (empty($row)) {
                $this->tokenCheckPassed = false;
            } else {
                if ($row->token == $this->accessToken && intval($row->uid) == $this->uid) {
                    $this->tokenCheckPassed = true;
                } else {
                    $this->tokenCheckPassed = false;
                }
            }
		}
	}
	public function getCurrentAction(){
        $action = \Route::current()->getActionName();
        list($class, $method) = explode('@', $action);
        $class = substr(strrchr($class,'\\'),1);
        return ['controller' => $class, 'method' => $method];
    }

    //当前控制器
    public function getCurrentControllerName(){
        return $this->getCurrentAction()['controller'];
    }
    //当前方法
    public function getCurrentMethodName(){
        return $this->getCurrentAction()['method'];
    }
}
