<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class IndexController extends Controller
{
    public function index()
    {
        $user = User::where('is_login',1)->get();
        
        return view('backend.index',[
            'user' => $user
        ]);
    }

    public function changePassword(Request $request) {
        if ($request->isMethod('post')) {
            # Validation
            $request->validate([
                'old_password' => 'required',
                'new_password' => 'required|confirmed',
            ]);


            #Match The Old Password
            if(!Hash::check($request->old_password, auth()->user()->password)){
                return back()->with("error", "Old Password Doesn't match!");
            }


            #Update the new Password
            User::whereId(auth()->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            return back()->with("status", "Password changed successfully!");
        } else {
            return view('backend.change-password');
        }
    }
}
