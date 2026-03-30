<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalLoginRequest;
use App\Models\PortalSession;
use App\Models\PppUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortalAuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $request->session()->regenerateToken();

        return response()
            ->view('portal.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    public function login(PortalLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $identifier = trim((string) $credentials['login']);
        $normalizedPhone = $this->normalizePhone($identifier);

        $pppUsers = PppUser::query()
            ->whereNotNull('password_clientarea')
            ->where(function ($query) use ($identifier, $normalizedPhone): void {
                $query->where('customer_id', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('nomor_hp', $normalizedPhone);
            })
            ->get();

        if ($pppUsers->isEmpty()) {
            return back()
                ->withErrors(['login' => 'Data pelanggan tidak ditemukan atau password portal belum diatur.'])
                ->onlyInput('login');
        }

        $matchedUser = $pppUsers->first(fn (PppUser $pppUser): bool => $this->passwordMatches(
            $credentials['password'],
            (string) $pppUser->password_clientarea
        ));

        if (! $matchedUser instanceof PppUser) {
            return back()
                ->withErrors(['password' => 'Password portal salah.'])
                ->onlyInput('login');
        }

        $token = Str::random(64);

        PortalSession::query()->create([
            'ppp_user_id' => $matchedUser->id,
            'token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'last_activity_at' => now(),
            'expires_at' => PortalSession::newExpiry(),
        ]);

        return redirect()
            ->route('portal.dashboard')
            ->withCookie(Cookie::make(
                'portal_session',
                $token,
                PortalSession::LIFETIME_MINUTES,
                '/',
                null,
                false,
                true
            ));
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->cookies->get('portal_session');

        if (is_string($token) && $token !== '') {
            PortalSession::query()->where('token', $token)->delete();
        }

        return redirect()
            ->route('portal.login')
            ->withCookie(Cookie::forget('portal_session'));
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        try {
            if (Hash::check($plainPassword, $storedPassword)) {
                return true;
            }
        } catch (\Throwable) {
        }

        return hash_equals($storedPassword, $plainPassword);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
