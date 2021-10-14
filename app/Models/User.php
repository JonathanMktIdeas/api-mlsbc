<?php

namespace App\Models;

use Firebase\JWT\JWT;
use App\Models\BaseModel;

class User extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Create a new token.
     *
     * @return string
     */
    function jwt() {
        $payload = [
            'iss' => "user",
            'sub' => $this->id,
            'iat' => time(),
            'exp' => time() + 60*60*24*365
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
