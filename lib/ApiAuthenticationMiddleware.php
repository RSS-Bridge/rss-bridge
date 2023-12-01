<?php

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
