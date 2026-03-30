<?php

namespace App\Http\Middleware;

use App\Models\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class PortalAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookies->get('portal_session');

        if (! is_string($token) || $token === '') {
            return redirect()->route('portal.login');
        }

        $portalSession = PortalSession::query()
            ->with('pppUser.profile')
            ->where('token', $token)
            ->first();

        if (! $portalSession instanceof PortalSession || $portalSession->isExpired()) {
            if ($portalSession instanceof PortalSession) {
                $portalSession->delete();
            }

            return redirect()
                ->route('portal.login')
                ->withCookie(Cookie::forget('portal_session'));
        }

        $portalSession->update([
            'last_activity_at' => now(),
            'expires_at' => PortalSession::newExpiry(),
        ]);

        $request->attributes->set('portal_session', $portalSession);
        $request->attributes->set('portal_ppp_user', $portalSession->pppUser);

        Cookie::queue(Cookie::make(
            'portal_session',
            $portalSession->token,
            PortalSession::LIFETIME_MINUTES,
            '/',
            null,
            false,
            true
        ));

        return $next($request);
    }
}
