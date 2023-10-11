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

    public function main(array $argv = []): void
    {
        if ($argv) {
            parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
            $request = $cliArgs;
        } else {
            if (Configuration::getConfig('authentication', 'enable')) {
                $authenticationMiddleware = new AuthenticationMiddleware();
                $authenticationMiddleware();
            }
            $request = array_merge($_GET, $_POST);
        }

        try {
            foreach ($request as $key => $value) {
                if (!is_string($value)) {
                    throw new \Exception("Query parameter \"$key\" is not a string.");
                }
            }

            $actionName = $request['action'] ?? 'Frontpage';
            $actionName = strtolower($actionName) . 'Action';
            $actionName = implode(array_map('ucfirst', explode('-', $actionName)));

            $filePath = __DIR__ . '/../actions/' . $actionName . '.php';
            if (!file_exists($filePath)) {
                throw new \Exception('Invalid action', 400);
            }
            $className = '\\' . $actionName;
            $action = new $className();

            $response = $action->execute($request);
            if (is_string($response)) {
                print $response;
            } elseif ($response instanceof Response) {
                $response->send();
            }
        } catch (\Throwable $e) {
            self::$logger->error('Exception in RssBridge::main()', ['e' => $e]);
            http_response_code(500);
            print render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]);
        }
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
