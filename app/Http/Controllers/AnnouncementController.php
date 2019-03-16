<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;
use App\Model\Announcement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class AnnouncementController extends Controller{
    public function create(Request $request){
        $params = $request->all();
        if(empty($params)){
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

        if($row->type != Merchant::TYPE_ADMIN){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '非管理员无权发布公告'
            ]);
        }

        $title = !empty($data['title'])? $data['title'] : '';
        $content = !empty($data['content'])? $data['content'] : '';
        $publishUid = $loginUid;

        $announcement = Announcement::where(['title'=>$title,'status' => 1])->first();
        if(!empty($announcement)){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '不能发布同标题公告'
            ]); 
        }

        $ann = new Announcement();
        $ann->title = $title;
        $ann->content = $content;
        $ann->publish_uid = $loginUid;
        $ann->status = 1; //已发布
        $ann->create_time = time();
        $ann->update_time = time();
        if(!$ann->save()){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '公告发布失败'
            ]);
        }

        return response()->json([
            'error_code' => 0, 
            'error_msg'  => '公告发布成功'
        ]);
    }


    public function edit(Request $request){
        $params = $request->all();
        if(empty($params)){
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

        if($row->type != Merchant::TYPE_ADMIN){
            return response->json([
                'error_code' => -1, 
                'error_msg' => '非管理员无权修改公告'
            ]);
        }
        if(empty($data['ann_id'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '请传入需要编辑的公告id'
            ]);
        }

        $id = $data['ann_id'];
        $ann = Announcement::where('id',$id)->first();
        if(empty($ann)){
            return response()->json([
                'error_code' => -1,
                'error_msg' => '此公告不存在，无法编辑'
            ]); 
        }

        if(isset($data['title'])){
            if($data['title'] == $ann->title){
                return response()->json([
                    'error_code' => -1, 
                    'error_msg' => '公告标题不可以重复'
                ]);
            } 
            $ann->title = $data['title'];
        }

        if(isset($data['content'])){
            $ann->content = $data['content'];
        }
        $ann->update_time = time();
        if(!$ann->save()){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '公告修改失败'
            ]);
        }
        return response()->json([
            'error_code' => 0,  
            'error_msg'  => '公告修改成功'
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

		$keyword = isset($data['keyword'])? $data['keyword'] : '';
		$pagination = isset($data['pagination'])? $data['pagination'] : 10;

        $lists = DB::table('announcement')
            ->select(DB::raw('id, title,content,create_time,update_time'))
            ->paginate($pagination);
        if(!empty($title)){
            $lists = DB::table('announcement')
                ->select(DB::raw('id, title,content,create_time,update_time'))
                ->where('title', 'like', '%'.$keyword.'%')
                ->orWhere('content', 'like', '%'.$keyword.'%')
                ->paginate($pagination);
        }

        return response()->json([
            'error_code' => 0, 
            'error_msg'  => '公告列表获取成功',
            'data'  => $lists
        ]);
    }

    public function info(Request $request){
		$params = $request->all();
        if(empty($params['data'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg' => '请求参数为空'
            ]); 
        }
		$data = $params['data'];
        if(empty($data['ann_id'])){
            return response()->json([
                'error_code' => -1, 
                'error_msg'  => '未传入公告id'
            ]);
        }
        $id = $data['ann_id'];
        $ann = Announcement::where('id',$id)->first();
        if(empty($ann)){
            return response()->json([
                'error_code' => -1, 
                'error_msg'  => '获取公告数据为空'
            ]);
        }
        return response()->json([
            'error_code' => 0,
            'error_msg'  => '获取公告数据成功',
            'data' => $ann
        ]);
    }

    public function del(Request $request){
        $params = $request->all();
        if(empty($params)){
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

        if($row->type != Merchant::TYPE_ADMIN){
            return response->json([
                'error_code' => -1, 
                'error_msg' => '非管理员无权修改公告'
            ]);
        }
        if(empty($data['ann_id'])){
            return response()->json([
                'error_code' => -1,
                'error_msg'  => '请传入需要编辑的公告id'
            ]);
        }

        $id = $data['ann_id'];
        $ann = Announcement::where('id',$id)->first();
        if(empty($ann)){
            return reponse()->json([
                'error_code' => -1, 
                'error_msg'  => '此公告不存在，无法删除'
            ]);
        }
        $ann->delete();
		return response()->json([
			'error_code' => 0,
			'error_msg' => '公告删除成功',
		]);
    }
}
