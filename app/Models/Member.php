<?php

namespace App\Models;

use App\Models\BaseModel;
use Firebase\JWT\JWT;
use App\Models\Agency;
use App\Models\MemberPayment;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends BaseModel
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone', 'mobile', 'password', 'email', 'agency_id', 'customer_op',
        'sync_date', 'is_admin', 'ignore_flex', 'photo', 'last_auth', 'last_email',
        'password_updated', 'board_sort', 'board', 'board_title',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'agency_id', 'customer_op'
    ];

    /**
     * Create a new token.
     *
     * @return string
     */
    function jwt() {
        $payload = [
            'iss' => "member",
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

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function membership()
    {
        return $this->hasMany(MemberPayment::class);
    }
}
