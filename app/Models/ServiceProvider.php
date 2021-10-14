<?php

namespace App\Models;

use App\Models\BaseModel;

class ServiceProvider extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'path', 'phone', 'email', 'name', 'label', 'category_id'
    ];
}
