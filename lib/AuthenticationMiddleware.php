<?php

final class AuthenticationMiddleware
{
    public function __construct()
    {
        if (Configuration::getConfig('authentication', 'password') === '') {
            throw new \Exception('The authentication password cannot be the empty string');
        }
    }

    public function __invoke(): void
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? null;
        $password = $_SERVER['PHP_AUTH_PW'] ?? null;

        if ($user === null || $password === null) {
            print $this->renderAuthenticationDialog();
            exit;
        }
        if (
            Configuration::getConfig('authentication', 'username') === $user
            && Configuration::getConfig('authentication', 'password') === $password
        ) {
            return;
        }
        print $this->renderAuthenticationDialog();
        exit;
    }

    private function renderAuthenticationDialog(): string
    {
        http_response_code(401);
        header('WWW-Authenticate: Basic realm="RSS-Bridge"');
        return render(__DIR__ . '/../templates/error.html.php', [
            'message' => 'Please authenticate in order to access this instance!',
        ]);
    }
}
