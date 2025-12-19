<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    // Show the login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        $dummyUsername = 'admin';
        $dummyPassword = 'P@ssw0rd';

        if ($username === $dummyUsername && $password === $dummyPassword) {
            session([
                'is_logged_in' => true,
                'user' => $dummyUsername,
            ]);
            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('username'));
    }

    public function loginDirect() {
        $request = request();
        $request->merge([
            'username' => 'admin',
            'password' => 'P@ssw0rd',
        ]);

        return $this->login($request);
    }

    public function logout()
    {
        Session::invalidate();
        Session::flush();
        Session::regenerateToken();
        return redirect('https://sim.phcm.co.id/phcm-satusehat/public/login');
    }
}
