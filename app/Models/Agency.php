<?php

namespace App\Models;

use App\Models\BaseModel;

class Agency extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone', 'mobile', 'email'
    ];
}
