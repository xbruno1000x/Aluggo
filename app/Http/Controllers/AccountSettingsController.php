<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Proprietario;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountSettingsController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $is2FAEnabled = $user instanceof Proprietario && !is_null($user->two_factor_secret);

        $qrCodeUrl = null;
        $twoFactorSecretPlain = null;
        if ($user instanceof Proprietario) {
            $qrCodeUrl = $is2FAEnabled ? $user->getTwoFactorQRCodeUrl() : null;
            if ($is2FAEnabled) {
                try {
                    $twoFactorSecretPlain = Crypt::decryptString($user->two_factor_secret);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    try {
                        $twoFactorSecretPlain = decrypt($user->two_factor_secret);
                    } catch (\Throwable $e2) {
                        $twoFactorSecretPlain = null;
                    }
                } catch (\Throwable $e) {
                    $twoFactorSecretPlain = null;
                }

                if ($twoFactorSecretPlain && preg_match('/^s:\d+:"(.*)";?$/', $twoFactorSecretPlain, $matches)) {
                    $twoFactorSecretPlain = $matches[1];
                }

                if (! empty($qrCodeUrl)) {
                    $trim = ltrim($qrCodeUrl);
                    if (strpos($trim, '<svg') === 0 || strpos($trim, '<?xml') === 0) {
                        $svg = $qrCodeUrl;
                        $svg = preg_replace('/\s+/', ' ', $svg);
                        $dataUri = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                        $qrCodeUrl = '<img src="' . $dataUri . '" alt="QR Code">';
                    }
                    elseif (strpos($trim, 'data:image/png;base64,') === 0) {
                        $qrCodeUrl = '<img src="' . $qrCodeUrl . '" alt="QR Code">';
                    }
                }
            }
        }

        return view('account.settings', compact('is2FAEnabled', 'qrCodeUrl', 'twoFactorSecretPlain'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var Proprietario $user */
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'A senha atual está incorreta.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'Senha alterada com sucesso!'
        ]);
    }

    public function toggleTwoFactorAuthentication(): RedirectResponse
    {
        $user = Auth::user();

        if ($user instanceof Proprietario) {
            if ($user->two_factor_secret) {
                $user->disableTwoFactorAuthentication();
            } else {
                $user->enableTwoFactorAuthentication();
            }
        }

        return redirect()->route('account.settings');
    }
}
