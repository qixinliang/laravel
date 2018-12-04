<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    //
	public $timestamps = false;
	protected $table = 'merchant';
	const TYPE_NORMAL_MER = 1;
	const TYPE_VIP_MER = 2;
	const TYPE_ADMIN = 3;

	const STATUS_NOT_COMPLETED = 0;
	const STATUS_COMPLETED = 1;
}
