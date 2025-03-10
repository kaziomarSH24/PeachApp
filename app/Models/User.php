<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    protected $guarded = ['id'];
    protected $appends = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'email_verified_at',
        'remember_token',
        'otp',
        'otp_expiry_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


     // Rest omitted for brevity

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

    //relationship
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function matching()
    {
        return $this->hasMany(Matching::class);
    }


    public function blockedUsers()
    {
        return $this->hasMany(Blocked::class, 'user_id', 'id');
    }

    public function blockedBy()
    {
        return $this->hasMany(Blocked::class, 'blocked_user_id', 'id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class);
    }

    //setting relationship
    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    //avatar attribute
    public function getAvatarAttribute($value)
    {
        return $value ? asset('storage/' . $value) : asset('img/default-avatar.jpg');
    }

    //full name attribute
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

}
