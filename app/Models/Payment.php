<?php

namespace App\Models;

use App\Models\BaseModel;
use Firebase\JWT\JWT;
use App\Models\Agency;

class Payment extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'member_id', 'amount', 'status', 'id_transaction',
    ];

}
