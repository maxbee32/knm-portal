<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable, HasRoles;

    // protected $guard_name ='admin-api';
    //  public $guard_name = ['admin-api','manager-api'];
    // 'admin-api'

    public $guard_name ='api';
    
    protected $fillable = [
        'email',
        'password',
        'username'
    ];




    protected $hidden = [
        'password',
        'remember_token',
    ];




    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


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
