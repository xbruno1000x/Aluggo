<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        return view('admin.email');
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
     * Processa o código de 2FA
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => 'required|numeric',
        ]);

        $user = Auth::user();

        if ($user instanceof Proprietario && $user->verifyTwoFactorCode($request->two_factor_code)) {
            return redirect()->route('admin.menu');
        }

        return back()->with('error', 'O código de autenticação fornecido é inválido.');
    }
}