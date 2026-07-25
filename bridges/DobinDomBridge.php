<?php

class DobinDomBridge extends BridgeAbstract
{
    const NAME = 'DobinDom (ДобинДом)';
    const URI = 'https://dobindom.ru';
    const DESCRIPTION = 'Broadcast recordings from DobinDom (ДобинДом), a Russian online platform for live-streamed psychology classes. Requires a subscriber account.';
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
                $description = $video['description'] ?? null;
                $timestamps = $video['timestamps'] ?? null;

                if ($posterUrl) {
                    $content = '<a href="' . htmlspecialchars($embedUrl) . '"><img src="' . htmlspecialchars($posterUrl) . '"></a>';
                } else {
                    $content = '<a href="' . htmlspecialchars($embedUrl) . '">'
                        . htmlspecialchars($video['title'])
                        . '</a>';
                }

                if ($description) {
                    $content .= '<p>' . htmlspecialchars($description) . '</p>';
                }

                if ($timestamps) {
                    $content .= '<hr>';
                    $content .= '<h4>Тайм-коды</h4>';
                    $content .= '<ul>';
                    foreach ($timestamps as $timestamp) {
                        $content .= '<li>' . $this->formatTimestamp($timestamp['time_seconds'])
                            . ' — ' . htmlspecialchars($timestamp['title'])
                            . '</li>';
                    }
                    $content .= '</ul>';
                }

                if (!empty($video['has_broadcast_text']) && isset($video['schedule_id'])) {
                    $text = $this->fetchBroadcastText($token, $video['schedule_id']);
                    if ($text) {
                        $content .= '<hr>';
                        $content .= '<h4>Текст</h4>';
                        $content .= $this->formatBroadcastText($text);
                    }
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

    public function getIcon()
    {
        // default location favicon.ico is broken
        return self::URI . '/favicon.svg';
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

    private function fetchBroadcastText(string $token, int $scheduleId): ?string
    {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $response = getContents(
            self::API_BASE . '/kinescope/cabinet-text/' . $scheduleId . '/state/?format=json',
            $headers
        );
        $data = Json::decode($response);

        return $data['content'] ?? null;
    }

    private function formatBroadcastText(string $text): string
    {
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        $html = '';
        foreach ($paragraphs as $paragraph) {
            $html .= '<p>' . nl2br(htmlspecialchars($paragraph)) . '</p>';
        }

        return $html;
    }

    private function formatTimestamp(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }
}
