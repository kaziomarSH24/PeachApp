<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = ['id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    //user_one_id
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    //user_two_id
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function getMessages($perPage = 10)
    {
        return $this->messages()->with([
            'sender' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'avatar', 'is_active');
            },
            'receiver' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'avatar', 'is_active');
            }
        ])->paginate($perPage);
    }


    public function getLatestMessage()
    {
        return $this->messages()->latest()->first();
    }


    public function getUnreadMessagesCount($userId)
    {
        return $this->messages()->where('sender_id', '!=', $userId)->where('status','!=', 'read')->count();
    }

    public function markMessagesAsRead($userId)
    {
        return $this->messages()->where('sender_id', '!=', $userId)->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    //get active status of the user
    public function getActiveStatus($userId)
    {
        return $this->users()->where('id', '!=', $userId)->first()->is_active;
    }


}
