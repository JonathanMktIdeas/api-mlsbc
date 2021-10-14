<?php

namespace App\Models;

use App\Models\BaseModel;

class Blog extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'image', 'category_id', 'content'
    ];
}
