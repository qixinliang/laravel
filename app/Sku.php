<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
	public $timestamps = false;
	protected $table = 'sku';

	const STATUS_NOT_AUDIT = 0;
	const STATUS_AUDIT_SUCCESS = 1;
	const STATUS_AUDIT_FAIL = -1;

    const SKU_TYPE_NORMAL = 0;
    const SKU_TYPE_VIP    = 1;
}
