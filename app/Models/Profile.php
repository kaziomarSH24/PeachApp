<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = ['id'];


    public function getAvatar()
    {
        if ($this->images) {
            $images = collect(json_decode($this->images))->map(fn($image) => asset('storage/' . $image));
            return $images->isNotEmpty() ? $images[0] : null;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
