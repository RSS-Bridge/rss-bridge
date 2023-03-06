<?php

final class RssBridge
{
    public function main(array $argv = [])
    {
        if ($argv) {
            parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
            $request = $cliArgs;
        } else {
            $request = array_merge($_GET, $_POST);
        }

        try {
            $this->run($request);
        } catch (\Throwable $e) {
            Logger::error('Exception in main', ['e' => $e]);
            http_response_code(500);
            print render(__DIR__ . '/../templates/error.html.php', ['e' => $e]);
        }
    }

    private function run($request): void
    {
        Configuration::verifyInstallation();

        $customConfig = [];
        if (file_exists(__DIR__ . '/../config.ini.php')) {
            $customConfig = parse_ini_file(__DIR__ . '/../config.ini.php', true, INI_SCANNER_TYPED);
        }
        Configuration::loadConfiguration($customConfig, getenv());

        set_error_handler(function ($code, $message, $file, $line) {
            if ((error_reporting() & $code) === 0) {
                return false;
            }
            $text = sprintf(
                '%s at %s line %s',
                sanitize_root($message),
                sanitize_root($file),
                $line
            );
            Logger::warning($text);
            if (Debug::isEnabled()) {
                print sprintf("<pre>%s</pre>\n", e($text));
            }
        });

        // There might be some fatal errors which are not caught by set_error_handler() or \Throwable.
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error) {
                $message = sprintf(
                    'Fatal Error %s: %s in %s line %s',
                    $error['type'],
                    sanitize_root($error['message']),
                    sanitize_root($error['file']),
                    $error['line']
                );
                Logger::error($message);
                if (Debug::isEnabled()) {
                    print sprintf("<pre>%s</pre>\n", e($message));
                }
            }
        });

        // Consider: ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
        date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

        if (Configuration::getConfig('authentication', 'enable')) {
            $authenticationMiddleware = new AuthenticationMiddleware();
            $authenticationMiddleware();
        }

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
            throw new \Exception(sprintf('Invalid action: %s', $actionName));
        }
        $className = '\\' . $actionName;
        $action = new $className();

        $response = $action->execute($request);
        if (is_string($response)) {
            print $response;
        } elseif ($response instanceof Response) {
            $response->send();
        }
    }
}
