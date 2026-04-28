<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(protected AuditoriaService $auditoria) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->destinoPosLogin();
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $request->session()->regenerate();
        $this->auditoria->registrar('login', Auth::user(), null, null, 'Login no painel');
        return $this->destinoPosLogin();
    }

    protected function destinoPosLogin()
    {
        return Auth::user()->isSuperAdmin()
            ? redirect()->route('super.dashboard')
            : redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $this->auditoria->registrar('logout', Auth::user(), null, null, 'Saiu do painel');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
