<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function register($data)
    {
        if (isset($data['profile_img'])) {
            $file = $data['profile_img'];
            $path = $file->store('profile_images', 'public');
            $path = Str::replace('profile_images/' , '' , $path);
            $data['profile_img'] = $path;
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'profile_img' => $data['profile_img']
        ]);
        $token = $user->createToken('token')->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function login($data)
    {
        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            $id = Auth::id();
            $user = User::findOrFail($id);
            $token = $user->createToken('token')->plainTextToken;
            return [
                'user' => $user, 
                'token' => $token
            ];
        }
    }
}
