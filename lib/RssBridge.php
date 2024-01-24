<?php

final class RssBridge
{
    private static CacheInterface $cache;
    private static Logger $logger;
    private static HttpClient $httpClient;

    public function __construct()
    {
        self::$logger = new SimpleLogger('rssbridge');
        if (Debug::isEnabled()) {
            self::$logger->addHandler(new StreamHandler(Logger::DEBUG));
        } else {
            self::$logger->addHandler(new StreamHandler(Logger::INFO));
        }
        self::$httpClient = new CurlHttpClient();
        $cacheFactory = new CacheFactory(self::$logger);
        if (Debug::isEnabled()) {
            self::$cache = $cacheFactory->create('array');
        } else {
            self::$cache = $cacheFactory->create();
        }
    }

    public function main(array $argv = []): Response
    {
        if ($argv) {
            parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
            $request = $cliArgs;
        } else {
            $request = array_merge($_GET, $_POST);
        }

        if (Configuration::getConfig('system', 'enable_maintenance_mode')) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', [
                'title'     => '503 Service Unavailable',
                'message'   => 'RSS-Bridge is down for maintenance.',
            ]), 503);
        }

        if (Configuration::getConfig('authentication', 'enable')) {
            if (Configuration::getConfig('authentication', 'password') === '') {
                return new Response('The authentication password cannot be the empty string', 500);
            }
            $user = $_SERVER['PHP_AUTH_USER'] ?? null;
            $password = $_SERVER['PHP_AUTH_PW'] ?? null;
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

        foreach ($request as $key => $value) {
            if (!is_string($value)) {
                return new Response(render(__DIR__ . '/../templates/error.html.php', [
                    'message' => "Query parameter \"$key\" is not a string.",
                ]), 400);
            }
        }

        $actionName = $request['action'] ?? 'Frontpage';
        $actionName = strtolower($actionName) . 'Action';
        $actionName = implode(array_map('ucfirst', explode('-', $actionName)));
        $filePath = __DIR__ . '/../actions/' . $actionName . '.php';
        if (!file_exists($filePath)) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Invalid action']), 400);
        }

        $className = '\\' . $actionName;
        $action = new $className();

        $response = $action->execute($request);

        if (is_string($response)) {
            $response = new Response($response);
        }
        return $response;
    }

    public static function getCache(): CacheInterface
    {
        return self::$cache;
    }

    public static function getLogger(): Logger
    {
        return self::$logger;
    }

    public static function getHttpClient(): HttpClient
    {
        return self::$httpClient;
    }
}
