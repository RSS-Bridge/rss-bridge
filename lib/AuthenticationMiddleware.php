<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

final class AuthenticationMiddleware
{
    public function __invoke(): void
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? null;
        $password = $_SERVER['PHP_AUTH_PW'] ?? null;

        if ($user === null || $password === null) {
            $this->renderAuthenticationDialog();
            exit;
        }
        if (
            Configuration::getConfig('authentication', 'username') === $user
            && Configuration::getConfig('authentication', 'password') === $password
        ) {
            return;
        }
        $this->renderAuthenticationDialog();
        exit;
    }

    private function renderAuthenticationDialog(): void
    {
        http_response_code(401);
        header('WWW-Authenticate: Basic realm="RSS-Bridge"');
        print render('error.html.php', ['message' => 'Please authenticate in order to access this instance !']);
    }
}
