<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Proprietario;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccountSettingsController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $is2FAEnabled = $user instanceof Proprietario && !is_null($user->two_factor_secret);

        $qrCodeUrl = null;
        if ($user instanceof Proprietario) {
            $qrCodeUrl = $is2FAEnabled ? $user->getTwoFactorQRCodeUrl() : null;
        }

        return view('account.settings', compact('is2FAEnabled', 'qrCodeUrl'));
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