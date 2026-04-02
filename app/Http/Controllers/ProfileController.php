<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index($id){
        return view('profile.index');
    }
   public function storee(Request $request, $id)
{

    $request->validate([
        'name' => 'required|string|max:255',
        'profile_img' => 'nullable|image|mimes:jpeg,png,jpg,avif,gif,webp'
    ]);

    $user = Auth::user();


    if ($request->hasFile('profile_img')) {

     
        if ($user->profile_img && Storage::disk('public')->exists('profile_images/' . $user->profile_img)) {
            Storage::disk('public')->delete('profile_images/' . $user->profile_img);
        }

      
        $file = $request->file('profile_img');
        $filename = time() . '_' . $file->getClientOriginalName();

        $file->storeAs('profile_images', $filename, 'public');

       
        $user->profile_img = $filename;
    }

    $user->name = $request->name;
    $user->save();

    return redirect()->back()->with('success', 'Profile updated successfully!');
}
}
