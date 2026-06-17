<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lightweight HTTP Basic auth for the staff & admin areas. Players never see
 * this — only the back-of-house screens. Credentials come from .env so the
 * MVP needs no user accounts or registration.
 */
class SimpleAccessGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('slush.staff_user');
        $pass = (string) config('slush.staff_password');

        // If no password configured, leave the gate open (local dev).
        if ($pass === '') {
            return $next($request);
        }

        $givenUser = (string) $request->getUser();
        $givenPass = (string) $request->getPassword();

        if (! hash_equals($user, $givenUser) || ! hash_equals($pass, $givenPass)) {
            return response('กรุณาเข้าสู่ระบบเจ้าหน้าที่', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="Slush Staff"',
            ]);
        }

        return $next($request);
    }
}
