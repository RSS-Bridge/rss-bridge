<?php

class DobinDomBridge extends BridgeAbstract
{
    const NAME = 'DobinDom (ДобинДом)';
    const URI = 'https://dobindom.ru';
    const DESCRIPTION = 'Returns archived videos from DobinDom';
    const MAINTAINER = 'anlar';

    const API_BASE = 'https://dobindom.ru/dobindom/backend';

    const PARAMETERS = [[
        'login' => [
            'name'     => 'Login (email)',
            'type'     => 'text',
            'required' => true,
        ],
        'password' => [
            'name'     => 'Password',
            'type'     => 'text',
            'required' => true,
        ],
    ]];

    public function collectData()
    {
        $token = $this->authenticate();
        $rooms = $this->fetchArchive($token);

        foreach ($rooms as $room) {
            $roomName = $room['room_name'];
            foreach ($room['videos'] as $video) {
                $posterUrl = $video['poster_url'] ?? null;
                $embedUrl = $video['embed_url'];

                if ($posterUrl) {
                    $content = '<a href="' . htmlspecialchars($embedUrl) . '">'
                        . '<img src="' . htmlspecialchars($posterUrl) . '">'
                        . '</a>';
                } else {
                    $content = '<a href="' . htmlspecialchars($embedUrl) . '">'
                        . htmlspecialchars($video['title'])
                        . '</a>';
                }

                $this->items[] = [
                    'uid'        => (string) $video['id'],
                    'title'      => $roomName . ' — ' . $video['title'],
                    'uri'        => $embedUrl,
                    'timestamp'  => strtotime($video['broadcast_datetime']),
                    'content'    => $content,
                    'categories' => [$roomName],
                ];
            }
        }
    }

    private function authenticate(): string
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => json_encode([
                'email'    => $this->getInput('login'),
                'password' => $this->getInput('password'),
            ]),
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $response = getContents(self::API_BASE . '/api/auth/login/', $headers, $opts);
        $data = Json::decode($response);

        if (empty($data['token'])) {
            throw new \Exception(
                'Authentication failed: no token in response'
            );
        }

        return $data['token'];
    }

    private function fetchArchive(string $token): array
    {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $response = getContents(self::API_BASE . '/kinescope/archive/', $headers);
        return Json::decode($response);
    }
}
