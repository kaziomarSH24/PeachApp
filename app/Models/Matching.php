<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matching extends Model
{
    protected $fillable = [
        'user_id',
        'matched_user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    
}
