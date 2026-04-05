<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Email varlığını sızdırmamak için her durumda aynı mesajı dön.
        // Saldırgan "bu e-posta kayıtlı mı?" bilgisini alamaz.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Eğer bu e-posta adresine kayıtlı bir hesap varsa, şifre sıfırlama bağlantısı gönderildi.');
    }
}
