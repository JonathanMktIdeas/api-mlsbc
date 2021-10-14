<?php

namespace App\Models;

use App\Models\BaseModel;

class ResourceLibrary extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'path', 'title', 'description', 'content'
    ];
}
