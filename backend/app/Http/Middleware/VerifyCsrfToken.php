<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'login',
        'logout',
        'register',
        'api/*', // If you are using an API prefix for your routes
    ];

    
    protected function tokensMatch($request)
{
    $token = $this->getTokenFromRequest($request);

    if (!is_string($sessionToken = $request->session()->token())) {
        return false;
    }

    \Log::info('CSRF token mismatch', [
        'sessionToken' => $sessionToken,
        'requestToken' => $token,
    ]);
    $tokensMatch = hash_equals($sessionToken, $token);
    \Log::info('Request Headers: ', $request->headers->all());

    if (!$tokensMatch) {
        \Log::info('CSRF token mismatch', [
            'sessionToken' => $sessionToken,
            'requestToken' => $token,
        ]);
    }

    return $tokensMatch;
}
}


