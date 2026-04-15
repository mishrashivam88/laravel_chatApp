<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{   
    protected $fillable=[
        'user_id',
        'otp'
    ];
    
   public function user(){
    return $this->belongsTo(User::class , 'user_id');
   }

}
