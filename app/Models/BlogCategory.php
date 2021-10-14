<?php

namespace App\Models;

use App\Models\BaseModel;

class BlogCategory extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];
}
