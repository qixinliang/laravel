<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

		$ret = Merchant::where(['username' => $username, 'password' => $password])->first();
		if(!isset($ret) || empty($ret)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '用户名或密码错误'
			]);
		}
		$uid = $ret->id;


		//加session处理
		$request->session()->put('uid',$uid);

		/*
		$ret = DB::insert('insert into user_token (token, uid, platform, create_time) values (?, ?, ? , ? )',[123456789, 7,77,123]);
		dump($ret);
		*/
		
		//写uid/token数据
		$token = new UserToken;
		
		$token->uid = $uid;
		$token->platform = 11;
		$token->token = md5($token->uid . '|' . $token->platform . '|' . time() . '|' . UserToken::generateCode(12));
		$token->create_time = time();
		dump($token);

		$ret = $token->save();
		dump($ret);

		
		/*
		$platform = 11;

		$data = [
			'token' => md5($uid . '|' . $platform . '|' . time() . '|' . UserToken::generateCode(12)),
			'uid' => $uid,
			'platform' => $platform,
			'create_time' => time()
		];
	
		$r = UserToken::create($data);

		dump($r);*/

		return response()->json([
			'error_code' => 0,
			'error_msg' => '登陆成功',
			//'data' => $ret
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
		$lists = Merchant::all();
		return response()->json([
			'error_code' => 0,	
			'error_msg' => '获取列表信息成功',
			'data' => $lists
		]);
	}


	//对应前端修改注意：
	//当前登陆用户为普通商户，添加按钮隐藏，不允许添加功能出现。
	public function add(Request $request){
		$method = $request->method();
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['login_name'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '登陆用户名为空'
			]);
		}
		$loginName = $data['login_name'];
		$column = Merchant::where('username',$loginName)->first();
		if(empty($column)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '未找到该登陆用户'
			]);
		}
		if($column->type == Merchant::TYPE_NORMAL_MER){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '当前登陆用户为普通商户，没有添加普通商户/管理员的权限'
			]);
		}

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
		$password = md5(data['password']);
		$repass   = md5($data['repass']);
		if($password != $repass){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '两个密码输入不一致，请重新确认'
			]);
		}
		$type = !empty($data['type'])? $data['type'] : Merchant::TYPE_NORMAL_MER;
		$logo = !empty($data['logo'])? $data['logo']: '';
		if($type == Merchant::TYPE_ADMIN && $column->type == Merchant::TYPE_VIP_MER ){
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
		$merchant->creator_uid = $column->id;

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
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['login_name'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '登陆用户名为空'
			]);
		}
		$loginName = $data['login_name'];
		$column = Merchant::where('username',$loginName)->first();
		if(empty($column)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '未找到该登陆用户'
			]);
		}

		$loginUid = $column->id;

		$username = $data['username'];
		$password = !empty($data['password'])? md5($data['password']) : '';
		$repass = !empty($data['repass'])? md5($data['repass']) : '';
		$logo = !empty($data['logo'])? $data['logo'] : '';

		$current = Merchant::where('username',$loginName)->first();
		if(empty($current)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '数据库中并未存在此条要修改的记录'
			]);
		}
		$uid = $current->id;
		$creatorUid = $current->creator_uid;
		if($loginUid != $uid || $loginUid != $creatorUid){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '权限错误，仅能修改自己或者自己发布的信息'
			]);
		}

		if(!empty($password) && !empty($repass)){
			$column->password = $password;
			$column->repass = $repass;
		}
		if(!empty($logo)){
			$column->logo = $logo;
		}
		$column->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '修改成功',
			'data' => $column
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
				'error_msg' => '登陆用户名为空'
			]);
		}
		$username = $data['username'];
		$column = Merchant::where('username',$username)->first();
		if(empty($column)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '未找到该登陆用户'
			]);
		}
		return response()->json([
			'error_code' => 0,
			'error_msg' => '获取用户信息成功',
			'data' => $column
		]);
	}


	public function erweima(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => '-1',
				'error_msg' => '请求参数为空'
			]);
		}
		$data = $params['data'];
		if(empty($data['merchant_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '商户id为空'
			]);
		}

		$data = $params['data'];
		$mid = $data['merchant_id'];
		$url = "abc.com/index.php?mid = {$mid}";

		
		$value = $url;         //二维码内容
  		$errorCorrectionLevel = 'L';  //容错级别
  		$matrixPointSize = 5;      //生成图片大小
  		//生成二维码图片
  		$filename = 'qrcode/'.time().'.png';
  		QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
  		$QR = $filename;        //已经生成的原始二维码图片文件
  		$QR = imagecreatefromstring(file_get_contents($QR));
  		//输出图片
  		imagepng($QR, 'qrcode.png');
  		imagedestroy($QR);
		return response()->json([
			'error_code' => 0,
			'error_msg' => '生成二维码成功',
			'data' => [
				'erweima' => $filename,
			]
		]);
	}
}
