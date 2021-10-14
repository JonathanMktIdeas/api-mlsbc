<?php

namespace App\Models;

use App\Models\BaseModel;

class Newsletter extends BaseModel
{

    protected $table = 'newsletter';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email'
    ];
}
