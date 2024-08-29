<?php

final class RssBridge
{
    private static Container $container;

    public function __construct(
        Container $container
    ) {
        self::$container = $container;
    }

    public function main(Request $request): Response
    {
        $action = $request->get('action', 'Frontpage');
        $actionName = strtolower($action) . 'Action';
        $actionName = implode(array_map('ucfirst', explode('-', $actionName)));
        $filePath = __DIR__ . '/../actions/' . $actionName . '.php';
        if (!file_exists($filePath)) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Invalid action']), 400);
        }

        $handler = self::$container[$actionName];

        $middlewares = [
            new SecurityMiddleware(),
            new MaintenanceMiddleware(),
            new BasicAuthMiddleware(),
            new TokenAuthenticationMiddleware(),
        ];
        $action = function ($req) use ($handler) {
            return $handler($req);
        };
        foreach (array_reverse($middlewares) as $middleware) {
            $action = fn ($req) => $middleware($req, $action);
        }
        return $action($request);
    }

    public static function getLogger(): Logger
    {
        // null logger is only for the tests not to fail
        return self::$container['logger'] ?? new NullLogger();
    }

    public static function getCache(): CacheInterface
    {
        return self::$container['cache'];
    }

    public static function getHttpClient(): HttpClient
    {
        return self::$container['http_client'];
    }
}
