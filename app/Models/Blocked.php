<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blocked extends Model
{
    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'reason',
        'blocked_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
