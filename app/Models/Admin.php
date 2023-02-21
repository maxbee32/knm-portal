<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{
    use HasApiTokens, HasFactory, Notifiable;



    protected $fillable = [
        'email',
        'password',
    ];




    protected $hidden = [
        'password',
        'remember_token',
    ];




    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
