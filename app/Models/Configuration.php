<?php

namespace App\Models;

use App\Models\BaseModel;

class Configuration extends BaseModel
{

    protected $table = 'configuration';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'value', 'type'
    ];
}
