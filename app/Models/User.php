<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Notifications\CustomResetPassword;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'gender',
        'country',
        'city',
        'birthday',
        'userName',
        'email',
        'phone',
        'password',
        'password_changed_at',
        'accountType',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date', // Cast birthday to date
        'password' => 'hashed',
        'password_changed_at' => 'datetime', // Add this cast for the new column
    ];

    public function task()
    {
        return $this->belongsToMany(Task::class);
    }

    //JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            // Include the timestamp of the last password change.
            // This is used by the JwtAuthMiddleware to invalidate tokens
            // issued before the last password reset.
            'password_changed_at' => $this->password_changed_at ? $this->password_changed_at->timestamp : null,
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }
    
   }

