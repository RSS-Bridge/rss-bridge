<?php

declare(strict_types=1);

/**
 * Make sure that only strings and arrays of strings are allowed in GET parameters
 */
class SecurityMiddleware implements Middleware
{
    public function __invoke(Request $request, $next): Response
    {
        foreach ($request->toArray() as $key => $value) {
            //TODO: verify if the type of parameter matches the type defined by the bridge in ParameterValidator
            if (is_string($value) || is_array($value) && array_all($value, 'is_string')) {
                continue;
            }
            return new Response(render(__DIR__ . '/../templates/error.html.php', [
                'message' => "Query parameter \"$key\" is not a string or array of strings.",
            ]), 400);
        }
        return $next($request);
    }
}
