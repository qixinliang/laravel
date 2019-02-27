<?php
namespace App\Http\Controllers;

use App\Sku;
use App\Model\UserToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SkuController extends Controller{
	public function add(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
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

		$data = $params['data'];
		if(empty($data['sku_name'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品名称为空'
			]);
		}

		$sku = new Sku();
		$sku->sku_name 		= $data['sku_name'];
		$sku->valid_time 	= !empty($data['valid_time'])? $data['valid_time'] : 0;
		$sku->logo 			= !empty($data['logo'])? $data['logo'] : '';
		$sku->redirect_url 	= !empty($data['redirect_url'])? $data['redirect_url'] : '';
		$sku->status 		= Sku::STATUS_NOT_AUDIT;
		$sku->add_time 		= time();
		$sku->creator_uid   = $uid;
		$sku->is_delete		= 0;
		$sku->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '奖品添加成功',
			'data' => $sku
		]);
	}

	public function edit(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
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

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = Sku::find($skuId);
		if(empty($sku)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '获取数据为空'
			]);
		}
		if($uid != $sku->creator_uid){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '非奖品发布者无权修改'	
			]);
		}
		if(isset($data['sku_name'])){
			$sku->sku_name = $data['sku_name'];
		}
		if(isset($data['valid_time'])){
			$sku->valid_time = $data['valid_time'];
		}
		if(isset($data['logo'])){
			$sku->logo = $data['logo'];
		}
		if(isset($data['redirect_url'])){
			$sku->redirect_url 	= $data['redirect_url'];
		}

		$sku->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '修改奖品数据成功',
			'data' => $sku
		]);
	}

	public function info(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = SKu::find($skuId);
		return response()->json([
			'error_code' => 0,
			'error_msg' => '获取sku信息成功',
			'data' => $sku
		]);
	}

	public function lists(Request $request){
		$params = $request->all();
		$pagination = 0;
		if(!empty($params['data'])){
			$data = $params['data'];
			$skuName = isset($data['sku_name'])? $data['sku_name'] : '';
			$pagination = isset($data['pagination'])? $data['pagination'] : 10;
		}
		if(isset($skuName) && !empty($skuName)){
			$lists = Sku::where('sku_name', 'like', '%'.$skuName.'%')->paginate($pagination); 
		}else{
			$lists = Sku::paginate($pagination);
		}
		return response()->json([
			'error_code' => 0,	
			'error_msg' => '获取列表信息成功',
			'data' => $lists
		]);
	}

	public function auditReject(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = Sku::find($skuId);
		if(empty($sku)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '获取数据为空'
			]);
		}
		$sku->status = Sku::STATUS_AUDIT_FAIL;
		$sku->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '审核拒绝完成',
			'data' => $sku
		]);
	}

	public function auditSuccess(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求参数为空'
			]);
		}

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = Sku::find($skuId);
		if(empty($sku)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '获取数据为空'
			]);
		}
		$sku->status = Sku::STATUS_AUDIT_SUCCESS;
		$sku->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '审核成功完成',
			'data' => $sku
		]);
	}

	public function audit(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
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

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = Sku::find($skuId);
		if(empty($sku)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '获取数据为空'
			]);
		}
		if($uid != $sku->creator_uid){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '非奖品发布者无权审核'	
			]);
		}
		if(isset($data['status'])){
			$sku->status = $data['status'];
		}

		$sku->save();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '审核完成',
			'data' => $sku
		]);
	}

	public function del(Request $request){
		$params = $request->all();
		if(empty($params) || empty($params['data'])){
			return response()->json([
				'error_code' => -1,
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

		$data = $params['data'];
		if(empty($data['sku_id'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '奖品id为空'
			]);
		}
		$skuId = $data['sku_id'];
		$sku = Sku::find($skuId);
		if(empty($sku)){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '获取数据为空'
			]);
		}
		if($uid != $sku->creator_uid){
			return response()->json([
				'error_code' => -1,
				'error_msg'  => '非奖品发布者无权删除'	
			]);
		}
		$sku->delete();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '奖品已删除',
		]);
	}


    //根据商户id获取它的所有优惠券
    //支持跨域调用
    public function getSkuByMid(Request $request){
		$params = $request->all();
		if(empty($params['data'])){
			return response()->json([
				'error_code' => -1,
				'error_msg' => '请求参数为空'
			]);
		}
        $data = $params['data'];
        if(!isset($data['merchant_id']) || empty($data['merchant_id'])){
            return response()->json([
                'error_code'  => -1,
                'error_msg' => '未传入商户id参数'
            ]);
        }

        $mid = $data['merchant_id'];
		$ret = Sku::where(['creator_uid' => $mid, 'is_delete' => 0])->get();
        return response()->json([
            'error_code' => 0,
            'error_msg' => 'success',
            'data' => $ret
        ]);
    }
}
