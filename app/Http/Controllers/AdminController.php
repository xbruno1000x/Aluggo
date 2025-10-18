<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Proprietario;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('proprietario')->check()) {
            return redirect()->route('admin.menu');
        }
        return view('admin.login');
    }    

    /**
     * Processa o login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        
        if (Auth::guard('proprietario')->attempt($credentials, $request->remember)) {
            $user = Auth::guard('proprietario')->user();

            if ($user->two_factor_secret) {
                return redirect()->route('admin.twofactor');
            }

            return redirect()->intended(route('admin.menu'));
        }
        
        return back()->withErrors([
            'email' => 'O email fornecido está incorreto.',
        ])->withInput($request->only('email', 'remember'))
        ->with('error', 'As credenciais fornecidas estão incorretas.');
    }    

    /**
     * Faz logout do administrador.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('proprietario')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Exibe o menu administrativo.
     */
    public function menu(): View
    {
        return view('admin.menu');
    }

    /**
     * Exibe o formulário de cadastro.
     */
    public function showRegisterForm(): View
    {
        return view('admin.register');
    }

    /**
     * Exibe o formulário de recuperação de senha.
     */
    public function showResetForm(): View
    {
        return view('admin.password-recovery');
    }

    /**
     * Processa o cadastro de um novo administrador.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:proprietarios',
            'telefone' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:proprietarios',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'nome.required' => 'O campo Nome é obrigatório.',
            'nome.string' => 'O campo Nome deve ser uma string.',
            'nome.max' => 'O campo Nome não pode ter mais de 255 caracteres.',
    
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.string' => 'O campo CPF deve ser uma string.',
            'cpf.size' => 'O campo CPF deve ter exatamente 11 caracteres.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
    
            'telefone.required' => 'O campo Telefone é obrigatório.',
            'telefone.string' => 'O campo Telefone deve ser uma string.',
            'telefone.max' => 'O campo Telefone não pode ter mais de 15 caracteres.',
    
            'email.required' => 'O campo Email é obrigatório.',
            'email.string' => 'O campo Email deve ser uma string.',
            'email.email' => 'O campo Email deve ser um endereço de e-mail válido.',
            'email.max' => 'O campo Email não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',
    
            'password.required' => 'O campo Senha é obrigatório.',
            'password.string' => 'O campo Senha deve ser uma string.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
    
            'password_confirmation.required' => 'O campo Confirmação de Senha é obrigatório.',
            'password_confirmation.same' => 'A confirmação de senha não corresponde à senha.',
        ]);
    
        Proprietario::create([
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'telefone' => $request->telefone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
    
        return redirect()->route('admin.login')->with('success', 'Cadastro realizado com sucesso!');
    }     
    
    /**
     * Exibe o formulário de 2FA
     */ 
    public function showTwoFactorForm(): View
    {
        return view('admin.twofactor');
    }

    /**
     * Processa o código de 2FA (recuperação por 2FA).
     */
    public function verifyTwoFactorRecovery(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:proprietarios,email',
            'two_factor_code' => 'required|numeric',
        ]);

        $user = Proprietario::where('email', $request->email)->first();

        /** @var \App\Models\Proprietario|null $user */

        if ($user && $user->verifyTwoFactorCode($request->two_factor_code)) {
            $token = Str::random(64);
            $expires = time() + 15 * 60;

            session([
                'reset_email' => $user->email,
                'reset_token' => $token,
                'reset_token_expires_at' => $expires,
            ]);

            return redirect()->route('admin.reset.password.form', ['token' => $token]);
        }

        return back()->with('error', 'O código de autenticação fornecido é inválido.');
    }

    /**
     * Exibe o formulário de redefinição de senha (verifica token em sessão).
     */
    public function showResetPasswordForm(string $token): View|RedirectResponse
    {
        $sessionToken = session('reset_token');
        $sessionEmail = session('reset_email');
        $expiresAt = session('reset_token_expires_at', 0);

        if (! $sessionToken || ! $sessionEmail || $sessionToken !== $token || time() > (int) $expiresAt) {
            return redirect()->route('admin.reset')->with('error', 'Token inválido ou expirado. Reinicie o processo de recuperação.');
        }

        return view('admin.reset-password', ['token' => $token, 'email' => $sessionEmail]);
    }

    /**
     * Processa a redefinição de senha enviada pelo formulário.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:proprietarios,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $sessionToken = session('reset_token');
        $sessionEmail = session('reset_email');
        $expiresAt = session('reset_token_expires_at', 0);

        if (! $sessionToken || $sessionToken !== $request->token || $sessionEmail !== $request->email || time() > (int) $expiresAt) {
            return back()->withErrors(['email' => 'Token inválido ou expirado.'])->withInput();
        }

        $user = Proprietario::where('email', $request->email)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        session()->forget(['reset_email', 'reset_token', 'reset_token_expires_at']);

        return redirect()->route('admin.login')->with('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
    }

    /**
     * Processa o código de 2FA durante o login.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => 'required|numeric',
        ]);

        $user = Auth::guard('proprietario')->user();
        
        /** @var \App\Models\Proprietario|null $user */
        if (! $user) {
            return redirect()->route('admin.login')->with('error', 'Sessão expirada. Faça login novamente.');
        }

        if ($user->verifyTwoFactorCode($request->two_factor_code)) {
            return redirect()->intended(route('admin.menu'));
        }

        return back()->with('error', 'Código de autenticação inválido.');
    }

}