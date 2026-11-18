<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('ticket-desk.api_token');
        $provided = $request->bearerToken();

        if ($expected === '' || ! hash_equals($expected, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
