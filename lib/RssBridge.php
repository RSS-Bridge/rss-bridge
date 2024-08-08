<?php

final class RssBridge
{
    private static Logger $logger;
    private static CacheInterface $cache;
    private static HttpClient $httpClient;

    public function __construct(
        Logger $logger,
        CacheInterface $cache,
        HttpClient $httpClient
    ) {
        self::$logger = $logger;
        self::$cache = $cache;
        self::$httpClient = $httpClient;
    }

    public function main(array $argv = []): Response
    {
        // The check for maintenance mode should always occur first since it has no
        //   dependencies, and nothing else needs to come before it for bootstrapping.
        if (Configuration::getConfig('system', 'enable_maintenance_mode')) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', [
                'title'     => '503 Service Unavailable',
                'message'   => 'RSS-Bridge is down for maintenance.',
            ]), 503);
        }

        if ($argv) {
            parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
            $request = Request::fromCli($cliArgs);
        } else {
            $request = Request::fromGlobals();
        }

        // HTTP Basic auth check
        if (Configuration::getConfig('authentication', 'enable')) {
            if (Configuration::getConfig('authentication', 'password') === '') {
                return new Response('The authentication password cannot be the empty string', 500);
            }
            $user = $request->server('PHP_AUTH_USER');
            $password = $request->server('PHP_AUTH_PW');
            if ($user === null || $password === null) {
                $html = render(__DIR__ . '/../templates/error.html.php', [
                    'message' => 'Please authenticate in order to access this instance!',
                ]);
                return new Response($html, 401, ['WWW-Authenticate' => 'Basic realm="RSS-Bridge"']);
            }
            if (
                (Configuration::getConfig('authentication', 'username') !== $user)
                || (! hash_equals(Configuration::getConfig('authentication', 'password'), $password))
            ) {
                $html = render(__DIR__ . '/../templates/error.html.php', [
                    'message' => 'Please authenticate in order to access this instance!',
                ]);
                return new Response($html, 401, ['WWW-Authenticate' => 'Basic realm="RSS-Bridge"']);
            }
            // At this point the username and password was correct
        }

        // If the URL contains an encrypted token, then the rest of the current URL
        //   parameters are discarded and the encrypted token is decrypted, decompressed,
        //   and expanded into the Request object's 'get' container. The user should NEVER
        //   be redirected to another URL that would expose what params that are in the
        //   current page.
        if (
            $request->get(UrlEncryptionService::PARAMETER_NAME)
            && UrlEncryptionService::enabled()
        ) {
            try {
                $request->tryDecryptUrl();
            } catch (\Exception $e) {
                return new Response(
                    render(__DIR__ . '/../templates/error.html.php', ['message' => $e->getMessage()]),
                    401
                );
            }
        }

        foreach ($request->toArray() as $key => $value) {
            if (!is_string($value)) {
                return new Response(render(__DIR__ . '/../templates/error.html.php', [
                    'message' => "Query parameter \"$key\" is not a string.",
                ]), 400);
            }
        }

        // Add token as attribute to request
        $request = $request->withAttribute('token', $request->get('token'));

        // Token authentication check
        if (Configuration::getConfig('authentication', 'token')) {
            if (! $request->attribute('token')) {
                return new Response(render(__DIR__ . '/../templates/token.html.php', [
                    'message' => '',
                ]), 401);
            }
            if (! hash_equals(Configuration::getConfig('authentication', 'token'), $request->attribute('token'))) {
                return new Response(render(__DIR__ . '/../templates/token.html.php', [
                    'message' => 'Invalid token',
                ]), 401);
            }
        }

        $action = $request->get('action', 'Frontpage');
        $actionName = strtolower($action) . 'Action';
        $actionName = implode(array_map('ucfirst', explode('-', $actionName)));
        $filePath = __DIR__ . '/../actions/' . $actionName . '.php';
        if (!file_exists($filePath)) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Invalid action']), 400);
        }

        $className = '\\' . $actionName;
        $actionObject = new $className();

        $response = $actionObject($request);

        return $response;
    }

    public static function getLogger(): Logger
    {
        return self::$logger;
    }

    public static function getCache(): CacheInterface
    {
        return self::$cache;
    }

    public static function getHttpClient(): HttpClient
    {
        return self::$httpClient;
    }
}
