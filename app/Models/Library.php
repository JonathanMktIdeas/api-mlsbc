<?php

namespace App\Models;

use App\Models\BaseModel;

class Library extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'content'
    ];
}
