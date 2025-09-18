<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQrCode;
use Database\Factories\ProprietarioFactory;

class Proprietario extends Authenticatable
{
    /** @use HasFactory<ProprietarioFactory> */
    use HasFactory, Notifiable;

    protected static string $factory = ProprietarioFactory::class;

    protected $fillable = [
        'nome', 'cpf', 'telefone', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Habilita a autenticação de dois fatores
     */
    public function enableTwoFactorAuthentication(): void
    {
        $google2fa = new Google2FA();
        $this->two_factor_secret = encrypt($google2fa->generateSecretKey());
        $this->save();
    }

    /**
     * Desabilita a autenticação de dois fatores
     */
    public function disableTwoFactorAuthentication(): void
    {
        $this->two_factor_secret = null;
        $this->save();
    }

    /**
     * Verifica se o código fornecido é válido
     *
     * @param string $code
     * @return bool
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $google2fa = new Google2FA();
        return $google2fa->verifyKey(decrypt($this->two_factor_secret), $code);
    }

    /**
     * Retorna a URL do QR Code para o Google Authenticator
     *
     * @return string
     */
    public function getTwoFactorQRCodeUrl(): string
    {
        $google2fa = new Google2FAQrCode();
        $secret = decrypt($this->two_factor_secret);
        $companyName = 'Gestão imobiliaria';
        $email = $this->email;

        return $google2fa->getQRCodeInline($companyName, $email, $secret);
    }
}