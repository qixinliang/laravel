<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model;

class  Waiter extends Model{
    protected $table = 'waiter';
    //public $timestamps = false;

    const STATUS_NORMAL     = 1; //正常
}
