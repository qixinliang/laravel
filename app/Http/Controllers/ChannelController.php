<?php
/*
 * @端口控制器
 */
namespace App\Http\Controllers;

use App\Channel;
use App\Http\Controllers\Controller;

class ChannelController extends Controller{

	//show all channels
	public function index(){
		$channels = Channel::all()->toArray();
		return view('channels',['channels' => $channels]);
		var_dump($channels);
	}
}
