<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class MerchantController extends Controller{
	public function register(Request $request){
		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$username = $request->input('data.username');
		$password = md5($request->input('data.password'));
		//$captcha = $request->input('data.captcha');
		if(empty($username) || empty($password)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '用户名或者密码为空'
			]);
		}

		$ret = Merchant::where('username', $username)->first();
		if(!empty($ret)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '该用户名已被注册'
			]);	
		}

		$merchant = new Merchant();
		$merchant->username = $username;
		$merchant->password = $password;
		$merchant->repass   = $password;
		$merchant->type     = Merchant::TYPE_NORMAL_MER; //普通商户
		$merchant->status   = Merchant::STATUS_NOT_COMPLETED; //未完善资料
		//$merchant->captcha = $captcha;
		$merchant->add_time = time();
		if(!$merchant->save()){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '用户注册数据保存失败'
			]);
		}

		return response()->json([
			'error_code' => 0,	
			'error_msg' => '用户注册成功',
			'data' => $merchant
		]);
	}

	public function login(Request $request){
		$uid = $request->session()->get('uid');
		/*
		if(!empty($uid)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请勿重复登陆'
			]);
		}*/

		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$platform = $request->input('data.platform',0);
		$username = $request->input('data.username');
		$password = md5($request->input('data.password'));
		//$captcha = $request->input('data.captcha');
		if(empty($username) || empty($password)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '用户名或者密码为空'
			]);
		}

		$ret = Merchant::where(['username' => $username, 'password' => $password])->first();
		if(!isset($ret) || empty($ret)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '用户名或密码错误'
			]);
		}
		$uid = $ret->id;
		$userType = $ret->type;


		//加session处理
		$request->session()->put('uid',$uid);

		//写uid/token数据
		//1.token固定一个，只更新时间,更新时间的目的，在于更新了create_time，expire_time也会被动更新.
		//2.每次进来需要先清理redis里的数据，然后在save的时候，触发事件再设置。

		$data = [];
		$data['uid'] = intval($uid);
		$data['user_type'] = intval($userType);
		$row = UserToken::where(['uid' => $uid, 'platform' => $platform])->first();
		if(!isset($row) || empty($row)){
			$token = new UserToken;
			$token->uid = $uid;
			$token->platform = $platform;
			$token->token = md5($token->uid . '|' . $token->platform . '|' . time() . '|' . UserToken::generateCode(12));
			$token->create_time = time();
			//dump($token);
			$token->save();
			
			$data['token'] = $token->token;
		}else{
			//重置redis
			$key = UserToken::TOKEN_PREFIX . $row->token;
			Redis::del($key);

			$row->create_time = time();
			$row->save();
			$data['token'] = $row->token;
		}

		return response()->json([
			'error_code' => 0,
			'error_msg' => '登陆成功',
			'data' => $data
		]);
	}

	public function logout(Request $request){
		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		if(!isset($params['uid'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求uid为空'
			]);
		}

		$uid = $params['uid'];

		//FIXME 针对uid，删除token表的数据／redis的数据?
		$platform = 0;
		$row = UserToken::where(['uid' => $uid, 'platform' => $platform])->first();
		if(empty($row)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => 'token数据已经删除'
			]);
		}

		$key = UserToken::TOKEN_PREFIX . $row->token;
		Redis::del($key);
		$row->delete();

		//$request->session()->forget('uid');
		//$request->session()->flush();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '退出成功'
		]);
	}

	public function complete(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,	
				'error_msg' => '请求参数为空'
			]);	
		}
		$data = $params['data'];
		if(empty($data['id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '参数错误，未传入需要完善资料的用户'
			]);
		}
		$id = $data['id'];
		$merName	= isset($data['merchant_name'])? $data['merchant_name'] : '';
		$boss		= isset($data['boss'])? $data['boss'] : '';
		$tel		= isset($data['tel'])? $data['tel'] : '';
		$mobile		= isset($data['mobile'])? $data['mobile'] : '';
		$address	= isset($data['address'])? $data['address'] : '';
		$logo		= isset($data['logo'])? $data['logo'] : '';
		$licence	= isset($data['licence'])? $data['licence'] : '';

		$merchant = Merchant::find($id);
		if(empty($merchant)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '该用户并未注册'
			]);
		}

		$merchant->merchant_name = $merName;
		$merchant->boss = $boss;
		$merchant->tel = $tel;
		$merchant->mobile = $mobile;
		$merchant->address = $address;
		$merchant->logo = $logo;
		$merchant->licence = $licence;
		$merchant->status = Merchant::STATUS_COMPLETED; //已完善资料
		$merchant->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '商户资料完善成功',
			'data' => $merchant
		]);
	}

	public function lists(Request $request){
		$params = $request->all();
        if(empty($params['data'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '请求参数为空'
            ]); 
        }
		$data = $params['data'];
        if(!isset($data['id']) || empty($data['id'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '未传入当前登陆用户id'
            ]);
        }
        $id = $data['id'];
		$username = isset($data['username'])? $data['username'] : '';
		$pagination = isset($data['pagination'])? $data['pagination'] : 10;

		$row = Merchant::where('id',$id)->first();
        if(empty($row)){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '未获取到当前登陆商户信息'
            ]);
        }
        $rows = NULL;

        //普通商户
        if($row->type == Merchant::TYPE_NORMAL_MER){
            $rows = DB::table('merchant')
                ->select(DB::raw('id, username,add_time,type'))
                ->where('id', $id)
                ->paginate($pagination);
            if(!empty($username)){
                $rows = DB::table('merchant')
                    ->select(DB::raw('id, username,add_time,type'))
                    ->where('id', $id)
                    ->Where('username', 'like', '%'.$username.'%')
                    ->paginate($pagination);
            }
        }

        //代理商
        elseif($row->type == Merchant::TYPE_VIP_MER){
            $rows = DB::table('merchant')
                ->select(DB::raw('id, username,add_time,type'))
                ->where('id', $id)
                ->orWhere('creator_uid',$id)
                ->paginate($pagination);
                //->get();
            if(!empty($username)){
                $rows = DB::table('merchant')
                    ->select(DB::raw('id, username,add_time,type'))
                    ->Where('username', 'like', '%'.$username.'%')
                    ->Where(function ($query) use ($id){
                        $query->where('id', '=', $id)
                            ->orWhere('creator_uid', '=', $id);
                    })
                    ->paginate($pagination);
            }
        }

        //管理员
        else if($row->type == Merchant::TYPE_ADMIN){
            $rows = DB::table('merchant')
                ->select(DB::raw('id, username,add_time,type'))
                ->paginate($pagination);
            if(!empty($username)){
                $rows = DB::table('merchant')
                    ->select(DB::raw('id, username,add_time,type'))
                    ->Where('username', 'like', '%'.$username.'%')
                    ->paginate($pagination);
            }
        }

		return response()->json([
			'error_code' => 0,	
			'error_msg' => '获取列表信息成功',
			'data' =>$rows
		]);

        /*
		if(isset($username) && !empty($username)){
			$lists = Merchant::where('username', 'like', '%'.$username.'%')->paginate($pagination); 
		}else{
			$lists = Merchant::paginate($pagination);
		}*/
		return response()->json([
			'error_code' => 0,	
			'error_msg' => '获取列表信息成功',
			'data' =>$lists
		]);
	}


	//对应前端修改注意：
	//当前登陆用户为普通商户，添加按钮隐藏，不允许添加功能出现。
	public function add(Request $request){
		/*
		$uid = $request->session()->get('uid');
		if(empty($uid)){
			return response()->json([
				'error_code' => -1,	
				'error_msg' => '请先登陆'
			]);
		}*/

		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		if(!isset($params['uid'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求uid为空'
			]);
		}
		$uid = $params['uid'];

		$platform = 0;
		$tokenData = UserToken::where(['uid' => $uid, 'platform' => $platform])->first();
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

		$row = Merchant::find($uid);
		if(empty($row)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据异常，并未在用户表里找到该用户'
			]);
		}
		if($row->type == Merchant::TYPE_NORMAL_MER){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '当前登陆用户为普通商户，没有添加普通商户/管理员的权限'
			]);
		}


		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		$data = $params['data'];
		if(empty($data['username']) || empty($data['password']) || empty($data['repass'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '用户名或者密码为空'
			]);
		}

		$username = $data['username'];
		$ret = Merchant::where('username', $username)->first();
		if(!empty($ret)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '已有该用户／商户，请勿重复添加'
			]);	
		}
		$password = md5($data['password']);
		$repass   = md5($data['repass']);
		if($password != $repass){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '两个密码不一致，请确认'
			]);
		}
		$type = !empty($data['type'])? $data['type'] : Merchant::TYPE_NORMAL_MER;
		$logo = !empty($data['logo'])? $data['logo']: '';
		if($type == Merchant::TYPE_ADMIN && $row->type == Merchant::TYPE_VIP_MER ){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '普通商户／代理商无添加管理员的权限'
			]);
		}

		$merchant = new Merchant();
		$merchant->username = $username;
		$merchant->password = $password;
		$merchant->repass   = $repass;
		$merchant->type     = $type;
		$merchant->logo     = $logo;
		$merchant->creator_uid = $uid;

		if($type == Merchant::TYPE_NORMAL_MER || $type == Merchant::TYPE_VIP_MER){
			$merchant->status = Merchant::STATUS_NOT_COMPLETED;
		}else{
			$merchant->status = Merchant::STATUS_COMPLETED;
		}
		
		$merchant->add_time = time();
		if(!$merchant->save()){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '添加用户／商户保存失败'
			]);
		}

		return response()->json([
			'error_code' => 0,	
			'error_msg' => '添加用户／商户成功',
			'data' => $merchant
		]);
	}

	public function edit(Request $request){
		/*
		$loginUid = $request->session()->get('uid');
		if(empty($loginUid)){
			return response()->json([
				'error_code' => -1,	
				'error_msg' => '请先登陆'
			]);
		}
		*/
		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		if(!isset($params['uid'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求uid为空'
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
		$row = Merchant::find($loginUid);
		if(empty($row)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据异常，并未在用户表里找到该用户'
			]);
		}

		$data = $params['data'];
		if(empty($data['username'])){
			return response()->json([
				'error_code' => -1,
				"error_msg"  => '请提供需要修改的商户名'
			]);
		}
		$username = $data['username'];

		$password = !empty($data['password'])? md5($data['password']) : '';
		$repass = !empty($data['repass'])? md5($data['repass']) : '';
		$logo = !empty($data['logo'])? $data['logo'] : '';

		if($password != $repass){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '密码不一致'
			]);
		}

		$current = Merchant::where('username',$username)->first();
		if(empty($current)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据库中并未存在此条要修改的记录'
			]);
		}

		$uid = $current->id;
		$creatorUid = $current->creator_uid;
		if($loginUid != $uid && $loginUid != $creatorUid){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '权限错误，仅能修改自己或者自己创建的商户数据'
			]);
		}

		if(!empty($password) && !empty($repass)){
			$current->password = $password;
			$current->repass = $repass;
		}
		if(!empty($logo)){
			$current->logo = $logo;
		}
		$current->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '修改成功',
			'data' => $current
		]);
	}

	public function del(Request $request){
		$params = $request->all();
		if(empty($params)){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		if(!isset($params['uid'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求uid为空'
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
		$row = Merchant::find($loginUid);
		if(empty($row)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据异常，并未在用户表里找到该用户'
			]);
		}

		$data = $params['data'];
		if(empty($data['merchant_id'])){
			return response()->json([
				'error_code' => -1,
				"error_msg"  => '请提供需要修改的商户id'
			]);
		}

		$current = Merchant::find($data['merchant_id']);
		if(empty($current)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据库中并未存在此条要删除的记录'
			]);
		}

		$uid = $current->id;
		$creatorUid = $current->creator_uid;
		if($loginUid != $uid && $loginUid != $creatorUid){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '权限错误，仅能删除自己或者自己创建的商户数据'
			]);
		}

		$current->delete();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '删除成功',
		]);
	}

	public function info(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['username'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '要查找的用户名为空'
			]);
		}
		$username = $data['username'];
		$row = Merchant::where('username',$username)->first();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '获取用户信息成功',
			'data' => $row
		]);
	}

	public function erweima(Request $request){
		require_once __DIR__ . '/../../../vendor/phpqrcode/phpqrcode.php';
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

		$value 					= "abc.com/index.php?mid = {$mid}"; //二维码内容
  		$errorCorrectionLevel 	= 'L'; //容错级别
  		$matrixPointSize 		= 5;   //生成图片大小
		$basepath = '/qrcode/' . $mid .'_'. time() . '.png';
  		$filename 				= $_SERVER['DOCUMENT_ROOT'] . $basepath;//生成二维码图e
		file_put_contents($filename,'');
		$final = $request->server()['HTTP_HOST'] . $basepath;

  		\QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
  		$QR = $filename; //已经生成的原始二维码图片文件
  		$QR = imagecreatefromstring(file_get_contents($QR));

  		imagepng($QR, 'qrcode.png');//输出图片
  		imagedestroy($QR);

		$row->erweima = $final;
		$row->save();

		return response()->json([
			'error_code' => 0,
			'error_msg' => '生成二维码成功',
			'data' => [
				'erweima' => $final,
			]
		]);
	}
}
