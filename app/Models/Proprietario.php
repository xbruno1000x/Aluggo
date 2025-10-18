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
        $this->two_factor_secret = \Illuminate\Support\Facades\Crypt::encryptString($google2fa->generateSecretKey());
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
     * Decripta o segredo 2FA (compatível com encrypt() e encryptString())
     *
     * @return string|null
     */
    private function decryptTwoFactorSecret(): ?string
    {
        if (empty($this->two_factor_secret)) {
            return null;
        }

        try {
            // Tenta decriptar com decryptString (novo método, sem serialização)
            return \Illuminate\Support\Facades\Crypt::decryptString($this->two_factor_secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Se falhar, tenta com decrypt (método antigo, com serialização)
            try {
                return decrypt($this->two_factor_secret);
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    /**
     * Verifica se o código fornecido é válido
     *
     * @param string $code
     * @return bool
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $secret = $this->decryptTwoFactorSecret();
        if (!$secret) {
            return false;
        }

        $google2fa = new Google2FA();
        return $google2fa->verifyKey($secret, $code);
    }

    /**
     * Retorna a URL do QR Code para o Google Authenticator
     *
     * @return string
     */
    public function getTwoFactorQRCodeUrl(): string
    {
        $secret = $this->decryptTwoFactorSecret();
        if (!$secret) {
            return '';
        }

        $google2fa = new Google2FAQrCode();
        $companyName = 'Gestão imobiliaria';
        $email = $this->email;

        return $google2fa->getQRCodeInline($companyName, $email, $secret);
    }
}