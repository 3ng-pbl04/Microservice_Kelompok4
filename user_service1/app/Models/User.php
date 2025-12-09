<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi mass-assign
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Kolom yang disembunyikan saat JSON response
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast atribut
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
