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
            if (is_string($value)) continue;
            //TODO: Maybe stricter checking for arrays?
            // Not required technically because the values are properly checked
            // in ParameterValidator, so basic check like this should be OK.
            if (is_array($value)) {
                $flag = true;
                foreach ($value as $v)
                    if (!($flag = is_string($v))) break;
                if ($flag) continue;
            }
            return new Response(render(__DIR__ . '/../templates/error.html.php', [
                'message' => "Query parameter \"$key\" is not a string or array of strings.",
            ]), 400);
        }
        return $next($request);
    }
}
