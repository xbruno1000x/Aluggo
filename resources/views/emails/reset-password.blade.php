<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Aluggo</title>
    <style>
        body { background-color: #f7f7f7; font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 24px 0; }
        .email { width: 100%; max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .header { background: #2563eb; color: #ffffff; padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 8px 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 32px 24px; }
        .content h2 { font-size: 18px; margin: 0 0 16px; color: #111827; }
        .content p { margin: 0 0 16px; line-height: 1.5; }
        .button { display: inline-block; background: #16a34a; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; margin: 16px 0; }
        .button:hover { background: #15803d; }
        .info { font-size: 12px; color: #6b7280; margin-top: 24px; }
        .footer { padding: 20px 24px; text-align: center; font-size: 12px; color: #6b7280; }
        .footer a { color: #2563eb; text-decoration: none; }
        @media (max-width: 600px) {
            .email { margin: 0 16px; }
            .content, .header, .footer { padding: 24px 20px; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="email">
        <div class="header">
            <h1>Recuperação de Senha</h1>
            <p>Aluggo</p>
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
