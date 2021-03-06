<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Client;
use App\User;
use App\UserSocialAccount;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/option';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectToProvider(string $driver)
    {
        if(Auth::user()){
            return redirect()->route('option');
        }
        return Socialite::driver($driver)->redirect();
    }

    public function handleProviderCallback(string $driver)
    {
        if(Auth::user()){
            return redirect()->route('option');
        }


        if( ! request()->has('code') || request()->has('denied')) {
            session()->flash('message', ['danger', __("Inicio de sesión cancelado")]);
            return redirect('ingreso');
        }

        $socialUser = Socialite::driver($driver)->user();
        $user = null;
        $success = true;
        $email = $socialUser->email;
        $check = User::whereEmail($email)->first();
        if($check) {
            $user = $check;
        } else {
            \DB::beginTransaction();
            try {
                $user = User::create([
                    "name" => $socialUser->name,
                    "email" => $email
                ]);
                UserSocialAccount::create([
                    "user_id" => $user->id,
                    "provider" => $driver,
                    "provider_uid" => $socialUser->id
                ]);
                Client::create([
                    "user_id" => $user->id,
                    "phone_id" => null,
                    "address_id"=>null
                ]);
            } catch (\Exception $exception) {
                // dd($exception);
                $success = $exception->getMessage();
                \DB::rollBack();
            }
        }

        if($success === true) {
            \DB::commit();
            auth()->loginUsingId($user->id);
            return redirect(route('/option'));
        }
        session()->flash('message', ['danger', $success]);
        return redirect('/ingreso');
    }
    public function logout(Request $request) {
        Auth::logout();
        return redirect('/ingreso');
      }
}
