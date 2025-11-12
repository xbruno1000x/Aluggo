<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Mail\Message;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use App\Models\Proprietario;
use App\Rules\StrongPassword;
use App\Rules\ValidCpf;
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
     * Envia o link de recuperação de senha por e-mail.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:proprietarios,email',
        ], [
            'email.required' => 'Informe o e-mail cadastrado.',
            'email.email' => 'Informe um e-mail válido.',
            'email.exists' => 'Não encontramos um cadastro com esse e-mail.',
        ]);

        $user = Proprietario::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Não encontramos um cadastro com esse e-mail.',
            ])->withInput();
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker('proprietarios');

    /** @var TokenRepositoryInterface $repository */
    $repository = $broker->getRepository();

    if ($repository instanceof DatabaseTokenRepository && $repository->recentlyCreatedToken($user)) {
            return back()->withErrors([
                'email' => 'Aguarde alguns minutos antes de solicitar um novo link.',
            ])->withInput();
        }

        $token = $broker->createToken($user);

        $resetUrl = URL::route('admin.reset.password.email.form', [
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            Mail::send('emails.reset-password', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => now()->addMinutes(config('auth.passwords.proprietarios.expire', 60)),
            ], function (Message $message) use ($user) {
                $message->to($user->email, $user->nome);
                $message->subject('Recuperação de Senha - Aluggo');
            });
        } catch (\Throwable $exception) {
            Log::error('Password recovery e-mail not sent', [
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Não foi possível enviar o e-mail de recuperação. Tente novamente em instantes.',
            ])->withInput();
        }

        return back()->with('status', 'Enviamos um link de recuperação para o seu e-mail.');
    }

    /**
     * Processa o cadastro de um novo administrador.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => ['required', 'string', new ValidCpf(), 'unique:proprietarios'],
            'telefone' => 'required|string|max:15',
            'email' => 'required|string|email|max:255|unique:proprietarios',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'nome.required' => 'O campo Nome é obrigatório.',
            'nome.string' => 'O campo Nome deve ser uma string.',
            'nome.max' => 'O campo Nome não pode ter mais de 255 caracteres.',
    
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.string' => 'O campo CPF deve ser uma string.',
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
    
        // Remove formatação do CPF antes de salvar
        $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf);
    
        $proprietario = Proprietario::create([
            'nome' => $request->nome,
            'cpf' => $cpfLimpo,
            'telefone' => $request->telefone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Autentica o usuário automaticamente após o cadastro
        Auth::guard('proprietario')->login($proprietario);
    
        return redirect()->route('admin.menu')->with('success', 'Cadastro realizado com sucesso! Bem-vindo ao Aluggo.');
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
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
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
     * Exibe o formulário de redefinição via e-mail.
     */
    public function showResetPasswordFormEmail(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');

        if (! $email) {
            return redirect()->route('admin.reset')->with('error', 'Link de recuperação inválido. Solicite um novo link.');
        }

        $user = Proprietario::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('admin.reset')->with('error', 'Link de recuperação inválido. Solicite um novo link.');
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker('proprietarios');

        if (! $broker->tokenExists($user, $token)) {
            return redirect()->route('admin.reset')->with('error', 'Este link de recuperação é inválido ou expirou.');
        }

        return view('admin.reset-password-email', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Processa a redefinição de senha via e-mail.
     */
    public function resetPasswordEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:proprietarios,email',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'email.required' => 'Informe o e-mail cadastrado.',
            'email.email' => 'Informe um e-mail válido.',
            'email.exists' => 'Não encontramos um cadastro com esse e-mail.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação de senha não corresponde.',
        ]);

        $status = Password::broker('proprietarios')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')->with('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
        }

        $messages = [
            Password::INVALID_TOKEN => 'O link de recuperação é inválido ou expirou.',
            Password::INVALID_USER => 'Não encontramos um cadastro com esse e-mail.',
            Password::RESET_THROTTLED => 'Aguarde alguns minutos antes de tentar novamente.',
        ];

        return back()->withErrors([
            'email' => $messages[$status] ?? 'Não foi possível redefinir a senha. Tente novamente.',
        ])->withInput($request->only('email'));
    }

    /**
     * Processa o código de 2FA durante o login.
     */
    public function verifyTwoFactor(): RedirectResponse
    {
        $request = request();

        $request->validate([
            'two_factor_code' => 'required|numeric',
        ]);

        $user = Auth::guard('proprietario')->user();
        
        /** @var \App\Models\Proprietario|null $user */
        if (! $user) {
            return redirect()->route('admin.login')->with('error', 'Sessão expirada. Faça login novamente.');
        }

        if ($user->verifyTwoFactorCode($request->input('two_factor_code'))) {
            return redirect()->intended(route('admin.menu'));
        }

        return back()->with('error', 'Código de autenticação inválido.');
    }

}