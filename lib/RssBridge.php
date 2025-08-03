<?php

final class RssBridge
{
    private Container $container;

    public function __construct(
        Container $container
    ) {
        $this->container = $container;
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

        $handler = $this->container[$actionName];

        $middlewares = [
            new BasicAuthMiddleware(),
            new CacheMiddleware($this->container['cache']),
            new ExceptionMiddleware($this->container['logger']),
            new SecurityMiddleware(),
            new MaintenanceMiddleware(),
            new TokenAuthenticationMiddleware(),
        ];
        $action = function ($req) use ($handler) {
            return $handler($req);
        };
        foreach (array_reverse($middlewares) as $middleware) {
            $action = fn ($req) => $middleware($req, $action);
        }
        return $action($request->withAttribute('action', $actionName));
    }
}
