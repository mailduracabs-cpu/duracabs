<?php

namespace App\Livewire\Auth;

use App\Mail\UserRegister;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;


#[Title('Register')]
class Register extends Component
{

    public $name;
    public $email;
    public $password;

    public function save(){
        $this->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:6|max:255'
        ]);

        // save user 
    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'password' => Hash::make($this->password),
        
    ]);

    $user->assignRole( 5 );

    

   

    // login user
        auth()->login($user);

        Mail::to(request()->user())->send(new UserRegister($user));


        return redirect()->intended();
    }

    


    public function render()
    {
        return view('livewire.auth.register');
    }
}
