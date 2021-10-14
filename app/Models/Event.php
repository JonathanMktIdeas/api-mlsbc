<?php

namespace App\Models;

use App\Models\BaseModel;
use Firebase\JWT\JWT;
use App\Models\Agency;
use App\Models\MemberPayment;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'start', 'end', 'content', 'all_day'
    ];
}
