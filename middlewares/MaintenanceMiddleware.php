<?php

declare(strict_types=1);

class MaintenanceMiddleware implements Middleware
{
    public function __invoke(Request $request, $next): Response
    {
        if (!Configuration::getConfig('system', 'enable_maintenance_mode')) {
            return $next($request);
        }
        return new Response(render(__DIR__ . '/../templates/error.html.php', [
            'title' => '503 Service Unavailable',
            'message' => 'RSS-Bridge is down for maintenance.',
        ]), 503);
    }
}
