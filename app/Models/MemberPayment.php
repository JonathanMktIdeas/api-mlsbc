<?php

namespace App\Models;

use App\Models\BaseModel;
use Firebase\JWT\JWT;
use App\Models\Agency;

class MemberPayment extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id', 'member_id', 'due'
    ];

}
