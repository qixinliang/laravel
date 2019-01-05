<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiniuController extends Controller{
	public function getToken(){
		require_once __DIR__ . '/../../../vendor/autoload.php';
		$accessKey = 'j789vq3-B01zX-ftg_BOKcQIOpBkaRRDPOQzm7ju';
		$secretKey = 'T4lR6XvXPlMklOB2BNPNg5t0DIkZX3zogoQrshL9';
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'timon';
		// 生成上传Token
		$token = $auth->uploadToken($bucket);
		return response()->json([
			'error_code' => 0,
			'error_msg' => '获取token成功',
			'token' => $token
		]);
	}
}
