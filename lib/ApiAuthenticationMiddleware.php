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

final class ApiAuthenticationMiddleware
{
    public function __invoke($request): void
    {
        $accessTokenInConfig = Configuration::getConfig('authentication', 'access_token');
        if (!$accessTokenInConfig) {
            $this->exit('Access token is not set in this instance', 403);
        }

        if (isset($request['access_token'])) {
            $accessTokenGiven = $request['access_token'];
        } else {
            $header = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $position = strrpos($header, 'Bearer ');

            if ($position !== false) {
                $accessTokenGiven = substr($header, $position + 7);
            } else {
                $accessTokenGiven = '';
            }
        }

        if (!$accessTokenGiven) {
            $this->exit('No access token given', 403);
        }

        if ($accessTokenGiven != $accessTokenInConfig) {
            $this->exit('Incorrect access token', 403);
        }
    }

    private function exit($message, $code)
    {
        http_response_code($code);
        header('content-type: text/plain');
        die($message);
    }
}
