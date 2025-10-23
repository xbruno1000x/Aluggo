<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Aluggo</title>
    <style>
        /* Paleta do site: azul primário (#0d6efd / bootstrap primary) e verde ação (#16a34a) */
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1f2937; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 24px 0; }
        .email { width: 100%; max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid rgba(15,23,42,0.04); }
        .header { background: linear-gradient(90deg,#0d6efd 0%,#2563eb 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
        .logo { max-width: 160px; height: auto; display: block; margin: 0 auto 12px; }
        .brand { font-size: 18px; font-weight: 700; margin: 0; }
        .content { padding: 28px 24px; }
        .content h2 { font-size: 18px; margin: 0 0 12px; color: #0f172a; }
        .content p { margin: 0 0 14px; line-height: 1.5; color: #374151; }
        .button { display: inline-block; background: #16a34a; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 16px 0; }
        .info { font-size: 13px; color: #6b7280; margin-top: 20px; }
        .footer { padding: 20px 24px; text-align: center; font-size: 13px; color: #6b7280; }
        .footer a { color: #0d6efd; text-decoration: none; }
        @media (max-width: 600px) {
            .email { margin: 0 12px; }
            .content, .header, .footer { padding: 20px; }
            .logo { max-width: 140px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="email">
        <div class="header">
            {{-- logo em build/images/logo.png — ajuste se o caminho for diferente no seu projeto --}}
            <img src="{{ asset('/images/aluggo_logo.png') }}" alt="Aluggo" class="logo" onerror="this.style.display='none'">
            <h1 class="brand">Recuperação de Senha - Aluggo</h1>
        </div>
        <div class="content">
            <h2>Olá, {{ $user->nome }}!</h2>
            <p>Recebemos uma solicitação para redefinir a senha da sua conta Aluggo. Se você fez esta solicitação, clique no botão abaixo para criar uma nova senha:</p>
            <p style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Redefinir minha senha</a>
            </p>
            <p>O link acima expira em {{ $expiresAt->locale('pt_BR')->diffForHumans(null, true) }}.</p>
            <p>Se você não solicitou a redefinição de senha, pode ignorar este e-mail com segurança. Sua senha atual continuará válida.</p>
            <p class="info">Por segurança, este link só pode ser usado uma vez. Caso ele expire, solicite um novo em nossa página de recuperação.</p>
        </div>
        <div class="footer">
            <p>Este e-mail foi enviado por <strong>Aluggo</strong>. Caso tenha dúvidas, entre em contato com nosso suporte.</p>
            <p><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
        </div>
    </div>
</div>
</body>
</html>
