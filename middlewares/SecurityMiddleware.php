<?php

declare(strict_types=1);

/**
 * Make sure that only strings and arrays are allowed in GET parameters
 */
class SecurityMiddleware implements Middleware
{
    public function __invoke(Request $request, $next): Response
    {
        foreach ($request->toArray() as $key => $value) {
            //TODO: Maybe stricter checking for arrays?
            // Not required technically because the values are properly checked
            // in ParameterValidator, so basic check like this should be OK.
            if (is_string($value) || (is_array($value) && !in_array(fn($v) => !is_string($v), $value))) continue;
            return new Response(render(__DIR__ . '/../templates/error.html.php', [
                'message' => "Query parameter \"$key\" is not a string.",
            ]), 400);
        }
        return $next($request);
    }
}
