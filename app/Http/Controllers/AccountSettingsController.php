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
                    // Decrypt returns serialized value, so unserialize it first
                    $decrypted = Crypt::decryptString($user->two_factor_secret);
                    // If it's serialized (starts with s:), unserialize it
                    if (is_string($decrypted) && preg_match('/^s:\d+:"(.*)";$/', $decrypted, $matches)) {
                        $twoFactorSecretPlain = $matches[1];
                    } else {
                        $twoFactorSecretPlain = $decrypted;
                    }
                } catch (\Throwable $e) {
                    $twoFactorSecretPlain = null;
                }

                // Some servers strip inline SVG output. If the provider returned a raw
                // SVG string, convert it to a data:image URI inside an <img> tag to
                // improve compatibility when rendering in browsers.
                if (! empty($qrCodeUrl)) {
                    $trim = ltrim($qrCodeUrl);
                    if (strpos($trim, '<svg') === 0 || strpos($trim, '<?xml') === 0) {
                        // encode safely for use in a data URI
                        $svg = $qrCodeUrl;
                        // remove newlines to keep URI compact
                        $svg = preg_replace('/\s+/', ' ', $svg);
                        $dataUri = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                        $qrCodeUrl = '<img src="' . $dataUri . '" alt="QR Code">';
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
