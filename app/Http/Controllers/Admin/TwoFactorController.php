<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function setup(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->google2fa_enabled && $request->session()->get('2fa_verified')) {
            return redirect()->route('admin.dashboard');
        }

        $google2fa = new Google2FA;

        if (empty($user->google2fa_secret)) {
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $otpauth = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $renderer = new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd);
        $qrSvg = (new Writer($renderer))->writeString($otpauth);

        return Inertia::render('Admin/TwoFactor/Setup', [
            'qrSvg' => $qrSvg,
            'secret' => $user->google2fa_secret,
            'alreadyEnabled' => (bool) $user->google2fa_enabled,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $google2fa = new Google2FA;

        $valid = $google2fa->verifyKey($user->google2fa_secret, $validated['code']);
        if (! $valid) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        $user->google2fa_enabled = true;
        $user->save();
        $request->session()->put('2fa_verified', true);

        return redirect()->route('admin.dashboard');
    }

    public function challenge(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->google2fa_enabled) {
            return redirect()->route('admin.2fa.setup');
        }
        if ($request->session()->get('2fa_verified')) {
            return redirect()->route('admin.dashboard');
        }
        return Inertia::render('Admin/TwoFactor/Challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $google2fa = new Google2FA;

        if (! $google2fa->verifyKey($user->google2fa_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        $request->session()->put('2fa_verified', true);
        return redirect()->route('admin.dashboard');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->google2fa_enabled = false;
        $user->google2fa_secret = null;
        $user->save();
        $request->session()->forget('2fa_verified');

        return redirect()->route('admin.2fa.setup');
    }
}
