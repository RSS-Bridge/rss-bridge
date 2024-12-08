<?php

declare(strict_types=1);

interface Middleware
{
    public function __invoke(Request $request, $next): Response;
}
