<?php
namespace App\Http\Controllers;

use App\Merchant;
use App\Model\UserToken;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

use DB;

class WaiterController extends Controller{
    public function bind(Request $request){
        echo "bind hexiaoyuan\n"; 
        var_dump($request->all()); 
    }
}
