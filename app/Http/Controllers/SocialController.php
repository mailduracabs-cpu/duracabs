<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();
            if($finduser){
                Auth::login($finduser);
                return redirect()->intended('/');
            }else{
                $finduser = User::where('email', $user->email)->first();
                if($finduser){
                    Auth::login($finduser);
                    return redirect()->intended('/');
                }else{
                    $newUser = User::create([
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id'=> $user->id,
                        'password' => encrypt('123456dummy')
                    ]);
                    Auth::login(user: $newUser);
                    return redirect()->intended('/');
                }
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }

    }
}
