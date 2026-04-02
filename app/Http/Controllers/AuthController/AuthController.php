<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Controllers\Controller;
use App\Service\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public $authService ;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService ;
    }

    public function register(Request $request){
         $request->validate([
            'name' => 'required' , 
            'email' => 'required|unique:users,email' , 
            'password' => 'required|confirmed',
            'profile_img' => 'required|image|mimes:jpeg,png,jpg,gif,avif,webp'
        ]);
        $data  = $this->authService->register($request->all());
        
        if($data)
        return redirect()->route('login')->with('success' , 'User Registered Successfully');
          
        return redirect()->route('register')->with('error' , 'Something went wrong ');
    }

    public function login(Request $request){

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $data = $this->authService->login($request->all());
         if($data)
        return redirect('/')->with('success' , 'User Registered Successfully');
          
        return redirect()->route('login')->with('error' , 'Something went wrong while log in ');
    }

    public function logout(){       
        Auth::logout();
        session()->regenerateToken();
        session()->invalidate();
        return redirect()->route('login')->with('success' , 'You are logged out!!');
    }

    
}
