<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model;

class  Promo extends Model{
    protected $table = 'promo';
    public $timestamps = false;

    const STATUS_NORMAL     = 1; //正常
    const STATUS_USED       = 2; //已使用
    const STATUS_INVALID    = 3; //作废
    const STATUS_DONATED    = 4; //已转赠

    const BY_OFFICAL_GAME   = 1; //小游戏获得
    const BY_OTHER_FRIEND   = 2; //朋友赠送

     /*
      * 创建优惠券券号
      * 生成规则: 年的后两位 + 月 + 日 时 + 分 + 秒 + 券的个数
      */
    public static function createNo()
    {
    	$count = Promo::count() + 1;
    	//var_dump($count);
        $timeArray = explode("-", date("Y-m-d-H-i-s", time()));
        return substr($timeArray[0], -2).$timeArray[1].$timeArray[2].$timeArray[3].
        $timeArray[4].$timeArray[5].$count;
    }

    public static function generate_code($length = 4) {
        return rand(pow(10,($length-1)), pow(10,$length)-1);
    }
}
