<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{   use SoftDeletes;
    protected $dates = ['created_at', 'updated_at'];
    
    protected $fillable = [
        'chat_messages' ,
        'sender_id',
        'receiver_id', 
        'seen',
        'delivered',
        'deleted_at'
    ];

    public function sender(){
        return $this->belongsTo(User::class , 'sender_id');
    }
    public function receiver(){
        return $this->belongsTo(User::class , 'receiver_id');
    }
    
}
