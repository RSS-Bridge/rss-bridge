<?php

declare(strict_types=1);

class TokenAuthenticationMiddleware implements Middleware
{
    public function __invoke(Request $request, $next): Response
    {
        if (! Configuration::getConfig('authentication', 'token')) {
            return $next($request);
        }

        $token = $request->get('token');

        if (! $token) {
            return new Response(render(__DIR__ . '/../templates/token.html.php', [
                'message'   => 'Missing token',
                'token'     => '',
            ]), 401);
        }

        if (! hash_equals(Configuration::getConfig('authentication', 'token'), $token)) {
            return new Response(render(__DIR__ . '/../templates/token.html.php', [
                'message'   => 'Invalid token',
                'token'     => $token,
            ]), 401);
        }

        $request = $request->withAttribute('token', $token);

        return $next($request);
    }
}
