<?php

final class RssBridge
{
    public function main(array $argv = [])
    {
        if ($argv) {
            parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
            $request = $cliArgs;
        } else {
            $request = $_GET;
        }

        try {
            $this->run($request);
        } catch (\Throwable $e) {
            error_log($e);
            $message = sprintf(
                'Uncaught Exception %s: %s at %s line %s',
                get_class($e),
                $e->getMessage(),
                trim_path_prefix($e->getFile()),
                $e->getLine()
            );
            http_response_code(500);
            print render('error.html.php', [
                'message' => $message,
                'stacktrace' => create_sane_stacktrace($e),
            ]);
        }
    }

    private function run($request): void
    {
        Configuration::verifyInstallation();
        Configuration::loadConfiguration();

        date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

        define('CUSTOM_CACHE_TIMEOUT', Configuration::getConfig('cache', 'custom_timeout'));

        $authenticationMiddleware = new AuthenticationMiddleware();
        if (Configuration::getConfig('authentication', 'enable')) {
            $authenticationMiddleware();
        }

        foreach ($request as $key => $value) {
            if (!is_string($value)) {
                throw new \Exception("Query parameter \"$key\" is not a string.");
            }
        }

        $actionFactory = new ActionFactory();
        $action = $request['action'] ?? 'Frontpage';
        $action = $actionFactory->create($action);
        $action->execute($request);
    }
}
