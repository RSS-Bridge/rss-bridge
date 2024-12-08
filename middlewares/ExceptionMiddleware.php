<?php

declare(strict_types=1);

class ExceptionMiddleware implements Middleware
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            $this->logger->error('Exception in ExceptionMiddleware', ['e' => $e]);

            return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), 500);
        }
    }
}