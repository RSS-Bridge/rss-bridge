<?php

declare(strict_types=1);

class LiveTennisApiBridge extends BridgeAbstract
{
    public const NAME = 'Live Tennis API';
    public const URI = 'https://livetennisapi.com/';
    public const DESCRIPTION = 'Tennis scores, schedule, results and fixtures for ATP, WTA, Challenger and ITF (needs a Live Tennis API key)';
    public const MAINTAINER = 'bensynapse';

    /**
     * 900 seconds, i.e. 15 minutes.
     *
     * One feed fetch costs exactly one Live Tennis API request, and the free tier
     * allows 100 requests per day. A feed served through this cache therefore costs
     * at most 86400 / 900 = 96 requests a day, which stays inside that quota no
     * matter how often a reader polls it. Every distinct set of feed parameters is
     * cached separately, so a host subscribing to several variants of this bridge on
     * one free key should either subscribe to fewer or use a paid key.
     */
    public const CACHE_TIMEOUT = 900;

    /**
     * The key is deliberately not a feed parameter: feed urls are shared, bookmarked
     * and logged. It is read from the [LiveTennisApiBridge] section of config.ini.php
     * (see docs/10_Bridge_Specific/LiveTennisApiBridge.md).
     *
     * 'required' is false on purpose. config.default.ini.php ships the key as an empty
     * string so hosts can see the setting exists, which means Configuration::getConfig()
     * returns '' rather than null and BridgeAbstract's own required-check can never
     * fire. getApiKey() below rejects the empty value with a message that says what to do.
     */
    public const CONFIGURATION = [
        'api_key' => [
            'required' => false,
        ],
    ];

    public const PARAMETERS = [[
        'mode' => [
            'name' => 'Feed',
            'type' => 'list',
            'title' => 'Which slate of matches to publish',
            'defaultValue' => 'live',
            'values' => [
                'Live matches' => 'live',
                'Today, still to start' => 'today',
                'Today, finished (needs a paid plan)' => 'results',
                'Upcoming fixtures' => 'fixtures',
            ],
        ],
        'tour' => [
            'name' => 'Tour',
            'type' => 'list',
            'title' => 'Each tour covers its own singles and doubles draws',
            'defaultValue' => 'all',
            'values' => [
                'All tours' => 'all',
                'ATP' => 'atp',
                'WTA' => 'wta',
                'Challenger' => 'challenger',
                'ITF' => 'itf',
                'Juniors' => 'juniors',
            ],
        ],
        'draw' => [
            'name' => 'Draw',
            'type' => 'list',
            'title' => 'Team ties, where one event type covers both, match neither singles nor doubles',
            'defaultValue' => 'all',
            'values' => [
                'Singles and doubles' => 'all',
                'Singles' => 'singles',
                'Doubles' => 'doubles',
            ],
        ],
        'player' => [
            'name' => 'Player name contains',
            'type' => 'text',
            'required' => false,
            'title' => 'Optional. Matched against both player names in the page that was fetched, case-insensitively',
            'exampleValue' => 'Sinner',
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'How many matches to ask the api for, 1 to 200',
            'defaultValue' => 50,
            'exampleValue' => 50,
        ],
    ]];

    private const API_BASE = 'https://api.livetennisapi.com/api/public/v1';

    public function collectData()
    {
        $apiKey = $this->getApiKey();
        $mode = (string) ($this->getInput('mode') ?? 'live');

        $query = [];
        if ($mode === 'fixtures') {
            $path = '/fixtures';
        } else {
            $path = '/matches';
            $today = gmdate('Y-m-d');
            if ($mode === 'today') {
                $query['status'] = 'upcoming';
                $query['from'] = $today;
                $query['to'] = $today;
            } elseif ($mode === 'results') {
                $query['status'] = 'completed';
                $query['from'] = $today;
                $query['to'] = $today;
            } else {
                $query['status'] = 'live';
            }
        }

        $tour = (string) ($this->getInput('tour') ?? 'all');
        if ($tour !== 'all' && $tour !== '') {
            $query['tour'] = $tour;
        }
        $draw = (string) ($this->getInput('draw') ?? 'all');
        if ($draw !== 'all' && $draw !== '') {
            $query['draw'] = $draw;
        }
        $query['limit'] = $this->getLimit();

        $rows = $this->fetchList($path, $query, $apiKey);
        $nameFilter = trim((string) ($this->getInput('player') ?? ''));

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $isFixture = ($mode === 'fixtures');
            if ($nameFilter !== '' && !self::namesContain($row, $isFixture, $nameFilter)) {
                continue;
            }
            $item = $isFixture ? $this->fixtureToItem($row) : $this->matchToItem($row);
            if ($item === null) {
                continue;
            }
            $this->items[] = $item;
        }
    }

    /**
     * The api's own player filter takes numeric player ids, not names, and resolving a
     * name to an id would cost a second request out of a 100 a day budget. So the name
     * is matched here, against the two player names only and not the whole title.
     */
    private static function namesContain(array $row, bool $isFixture, string $needle): bool
    {
        if ($isFixture) {
            $names = [self::text($row['player1_name'] ?? null), self::text($row['player2_name'] ?? null)];
        } else {
            $players = is_array($row['players'] ?? null) ? $row['players'] : [];
            $names = [self::playerName($players['p1'] ?? null), self::playerName($players['p2'] ?? null)];
        }
        foreach ($names as $name) {
            if ($name !== '' && stripos($name, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getApiKey(): string
    {
        $apiKey = $this->getOption('api_key');
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new \Exception(sprintf(
                'This bridge needs a Live Tennis API key. Put it in config.ini.php as %s, or set %s. Keys are issued at %s',
                '[LiveTennisApiBridge] api_key = "yourkey"',
                'RSSBRIDGE_LiveTennisApiBridge_api_key',
                'https://livetennisapi.com/'
            ));
        }
        return trim($apiKey);
    }

    private function getLimit(): int
    {
        $limit = $this->getInput('limit');
        if (!is_numeric($limit)) {
            return 50;
        }
        return max(1, min(200, (int) $limit));
    }

    /**
     * @return array<int, mixed> the rows of a list response
     */
    private function fetchList(string $path, array $query, string $apiKey): array
    {
        $url = self::API_BASE . $path . '?' . http_build_query($query);
        // The key travels as a header and never as ?token=: urls reach access logs,
        // browser history, the http cache key and exception messages.
        $headers = [
            'Accept: application/json',
            'X-API-Key: ' . $apiKey,
        ];

        try {
            $body = getContents($url, $headers);
        } catch (HttpException $e) {
            throw self::apiException($e);
        }

        try {
            $json = Json::decode($body);
        } catch (\JsonException $e) {
            throwServerException('The Live Tennis API returned a body that is not valid JSON');
        }
        if (!is_array($json) || !isset($json['data']) || !is_array($json['data'])) {
            throwServerException('The Live Tennis API response carried no data array');
        }
        return array_values($json['data']);
    }

    /**
     * Turn a failed request into a message that names the cause. The api key is never
     * part of it: the url carries no key and the raw exception message is not reused.
     */
    private static function apiException(HttpException $e): \Exception
    {
        $code = $e->getCode();
        $error = '';
        $detail = '';
        if ($e->response) {
            try {
                $decoded = Json::decode($e->response->getBody());
                if (is_array($decoded)) {
                    $error = is_string($decoded['error'] ?? null) ? $decoded['error'] : '';
                    $detail = is_string($decoded['detail'] ?? null) ? $decoded['detail'] : '';
                }
            } catch (\JsonException $jsonException) {
                // Not every error body is json. The status code below says enough.
            }
        }
        $suffix = '';
        if ($detail !== '') {
            $suffix = ' The api said: ' . $detail;
        } elseif ($error !== '') {
            $suffix = ' The api said: ' . $error;
        }

        if ($code === 401) {
            return new \Exception('The Live Tennis API rejected the configured api_key (401). Check [LiveTennisApiBridge] api_key.' . $suffix);
        }
        if ($code === 403) {
            return new \Exception('This feed is not included in the plan the configured api_key holds (403).' . $suffix);
        }
        if ($code === 429) {
            $retryAfter = $e->response ? $e->response->getHeader('retry-after') : null;
            $message = 'Rate limited by the Live Tennis API (429).';
            if (is_string($retryAfter) && $retryAfter !== '') {
                $message .= ' Retry after ' . $retryAfter . ' seconds.';
            }
            return new RateLimitException($message . $suffix);
        }
        if ($code === 400) {
            return new \Exception('The Live Tennis API rejected the query (400).' . $suffix);
        }
        return new \Exception(sprintf('The Live Tennis API request failed with status %s.%s', $code, $suffix));
    }

    private function matchToItem(array $match): ?array
    {
        $id = $match['id'] ?? null;
        if (!is_numeric($id)) {
            return null;
        }
        $id = (int) $id;

        $players = is_array($match['players'] ?? null) ? $match['players'] : [];
        $p1 = self::playerName($players['p1'] ?? null, 'Player 1');
        $p2 = self::playerName($players['p2'] ?? null, 'Player 2');

        $score = is_array($match['score'] ?? null) ? $match['score'] : null;
        $gamesText = self::formatGamesBySet($score === null ? null : ($score['games'] ?? null));
        $status = self::statusLabel($match, $p1, $p2);
        $tournament = self::text($match['tournament'] ?? null);

        $title = $p1 . ' v ' . $p2;
        if ($gamesText !== '') {
            $title .= ' ' . $gamesText;
        }
        $title .= ' (' . $status . ')';
        if ($tournament !== '') {
            $title .= ' — ' . $tournament;
        }

        return [
            'title' => $title,
            'uri' => self::API_BASE . '/matches/' . $id,
            'uid' => 'livetennisapi:match:' . $id,
            'timestamp' => self::matchTimestamp($match, $score),
            'author' => $tournament !== '' ? $tournament : 'Live Tennis API',
            'categories' => self::categories($match),
            'content' => self::matchContent($match, $score, $p1, $p2, $status, $gamesText),
        ];
    }

    private function fixtureToItem(array $fixture): ?array
    {
        $id = $fixture['id'] ?? null;
        if (!is_numeric($id)) {
            return null;
        }
        $id = (int) $id;

        $p1 = self::text($fixture['player1_name'] ?? null, 'Player 1');
        $p2 = self::text($fixture['player2_name'] ?? null, 'Player 2');
        $tournament = self::text($fixture['tournament'] ?? null);
        $startTime = self::text($fixture['start_time'] ?? null);
        $eventDate = self::text($fixture['event_date'] ?? null);

        // start_time is null until the order of play assigns one, which is a real
        // state rather than a gap, so fall back to the date and say so.
        if ($startTime !== '') {
            $when = 'starts ' . self::formatInstant($startTime);
        } elseif ($eventDate !== '') {
            $when = 'scheduled for ' . $eventDate . ', no time yet';
        } else {
            $when = 'no start time published';
        }

        $title = $p1 . ' v ' . $p2 . ' (' . $when . ')';
        if ($tournament !== '') {
            $title .= ' — ' . $tournament;
        }

        $rows = [];
        if ($tournament !== '') {
            $rows['Tournament'] = $tournament . self::suffix(self::text($fixture['round'] ?? null), ' — ');
        }
        $rows['When'] = ucfirst($when);
        if (self::text($fixture['surface'] ?? null) !== '') {
            $rows['Surface'] = self::text($fixture['surface'] ?? null);
        }
        if (self::text($fixture['tour'] ?? null) !== '') {
            $rows['Tour'] = self::text($fixture['tour'] ?? null);
        }
        if (self::text($fixture['status'] ?? null) !== '') {
            $rows['Status'] = self::text($fixture['status'] ?? null);
        }
        $rows['Players'] = $p1 . ' v ' . $p2;

        return [
            'title' => $title,
            'uri' => self::API_BASE . '/matches/' . $id,
            'uid' => 'livetennisapi:fixture:' . $id,
            'timestamp' => $startTime !== '' ? $startTime : $eventDate,
            'author' => $tournament !== '' ? $tournament : 'Live Tennis API',
            'content' => self::renderRows($rows),
        ];
    }

    /**
     * @param array|null $score the score object, which is null on a match with no score yet
     */
    private static function matchContent(array $match, ?array $score, string $p1, string $p2, string $status, string $gamesText): string
    {
        $rows = [];

        $tournament = self::text($match['tournament'] ?? null);
        if ($tournament !== '') {
            $rows['Tournament'] = $tournament . self::suffix(self::text($match['round'] ?? null), ' — ');
        }
        $surface = self::text($match['surface'] ?? null);
        if ($surface !== '') {
            $rows['Surface'] = $surface . (($match['indoor'] ?? false) === true ? ' (indoor)' : '');
        }
        $draw = self::text($match['draw'] ?? null);
        if ($draw !== '') {
            $rows['Draw'] = $draw;
        }
        $format = self::text($match['format'] ?? null);
        if ($format === 'BO5') {
            $rows['Format'] = 'best of five';
        } elseif ($format === 'BO3') {
            $rows['Format'] = 'best of three';
        }

        $rows['Status'] = $status;

        if ($score !== null) {
            $sets = $score['sets'] ?? null;
            if (is_array($sets) && isset($sets[0], $sets[1]) && is_numeric($sets[0]) && is_numeric($sets[1])) {
                $rows['Sets'] = (int) $sets[0] . '-' . (int) $sets[1];
            }
            if ($gamesText !== '') {
                $rows['Games by set'] = $gamesText;
            }

            $points = self::formatPoints($score);
            if ($points !== '') {
                $rows[($score['is_tiebreak'] ?? false) === true ? 'Tiebreak' : 'Current game'] = $points;
            }

            $server = self::serverNumber($score);
            if ($server !== null) {
                $rows['Serving'] = $server === 1 ? $p1 : $p2;
            }
            if (self::isBreakPoint($score)) {
                $rows['Break point'] = ($server === 1 ? $p2 : $p1) . ' is a point from the break';
            }

            $stamp = self::text($score['timestamp'] ?? null);
            if ($stamp !== '') {
                $rows['Score read at'] = self::formatInstant($stamp);
            }
        }

        $scheduled = self::text($match['scheduled_time'] ?? null);
        if ($scheduled !== '') {
            $rows['Scheduled'] = self::formatInstant($scheduled);
        }
        $liveAt = self::text($match['live_at'] ?? null);
        if ($liveAt !== '') {
            $rows['Last seen in play'] = self::formatInstant($liveAt);
        }

        $players = is_array($match['players'] ?? null) ? $match['players'] : [];
        $rows['Players'] = self::playerLine($players['p1'] ?? null, $p1) . ' v ' . self::playerLine($players['p2'] ?? null, $p2);

        return self::renderRows($rows);
    }

    /**
     * Is the receiver one point from breaking?
     *
     * True when the receiver holds advantage, or is at 40 while the server is behind.
     * 40-40 is deuce and 40-AD belongs to the server, so neither counts. A tiebreak is
     * excluded outright: its points are a running count, not 0/15/30/40, and there is
     * no service game to break.
     */
    private static function isBreakPoint(?array $score): bool
    {
        if ($score === null || ($score['is_tiebreak'] ?? false) === true) {
            return false;
        }
        $server = self::serverNumber($score);
        if ($server === null) {
            return false;
        }
        $points = $score['points'] ?? null;
        if (!is_array($points)) {
            return false;
        }
        $serverPoints = $points[$server - 1] ?? null;
        $receiverPoints = $points[$server === 1 ? 1 : 0] ?? null;
        if (!is_string($serverPoints) || !is_string($receiverPoints)) {
            return false;
        }
        if ($receiverPoints === 'AD') {
            return true;
        }
        return $receiverPoints === '40' && in_array($serverPoints, ['0', '15', '30'], true);
    }

    private static function serverNumber(?array $score): ?int
    {
        if ($score === null) {
            return null;
        }
        $server = $score['server'] ?? null;
        if (!is_numeric($server)) {
            return null;
        }
        $server = (int) $server;
        return ($server === 1 || $server === 2) ? $server : null;
    }

    /**
     * games is player-major: the first list is player 1's games per set, the second is
     * player 2's, so [[6, 3], [4, 4]] reads 6-4 in the first set and 3-4 in the second.
     *
     * @param mixed $games
     */
    private static function formatGamesBySet($games): string
    {
        if (!is_array($games) || !is_array($games[0] ?? null) || !is_array($games[1] ?? null)) {
            return '';
        }
        $first = array_values($games[0]);
        $second = array_values($games[1]);
        $sets = [];
        $setCount = max(count($first), count($second));
        for ($i = 0; $i < $setCount; $i++) {
            $a = $first[$i] ?? null;
            $b = $second[$i] ?? null;
            if (!is_numeric($a) || !is_numeric($b)) {
                continue;
            }
            $sets[] = (int) $a . '-' . (int) $b;
        }
        return implode(' ', $sets);
    }

    /**
     * In-game points, player-major. Entries can be null on a completed match, so both
     * sides must be strings before anything is shown.
     */
    private static function formatPoints(array $score): string
    {
        $points = $score['points'] ?? null;
        if (!is_array($points)) {
            return '';
        }
        $a = $points[0] ?? null;
        $b = $points[1] ?? null;
        if (!is_string($a) || !is_string($b) || $a === '' || $b === '') {
            return '';
        }
        return $a . '-' . $b;
    }

    private static function statusLabel(array $match, string $p1, string $p2): string
    {
        $status = self::text($match['status'] ?? null);
        if ($status !== 'completed') {
            $eventStatus = self::text($match['event_status'] ?? null);
            if ($status === 'live' && $eventStatus === 'Interrupted') {
                return 'live, interrupted';
            }
            if ($status === 'cancelled') {
                return $eventStatus !== '' ? 'cancelled, ' . strtolower($eventStatus) : 'cancelled';
            }
            return $status !== '' ? $status : 'unknown';
        }

        $winner = $match['winner'] ?? null;
        $winnerName = '';
        if (is_numeric($winner)) {
            $winnerName = ((int) $winner) === 1 ? $p1 : $p2;
        }
        $withdrew = $match['withdrew'] ?? null;
        $withdrewName = '';
        if (is_numeric($withdrew)) {
            $withdrewName = ((int) $withdrew) === 1 ? $p1 : $p2;
        }

        // Branch on outcome, the closed vocabulary, rather than on event_status spellings.
        $outcome = self::text($match['outcome'] ?? null);
        if ($outcome === 'retired') {
            return $withdrewName !== '' ? $withdrewName . ' retired' : 'retired';
        }
        if ($outcome === 'walkover') {
            return $winnerName !== '' ? 'walkover to ' . $winnerName : 'walkover';
        }
        if ($outcome === 'default') {
            return $winnerName !== '' ? 'default, ' . $winnerName . ' through' : 'default';
        }
        if ($outcome === 'abandoned') {
            return 'abandoned unfinished';
        }
        if ($outcome === 'unresolved') {
            // Every source lost the match before a result; no winner is asserted.
            return 'closed unfinished, result unresolved';
        }
        if ($winnerName !== '') {
            return $winnerName . ' won';
        }
        return 'completed';
    }

    /**
     * @param array|null $score
     * @return string the best instant available for this row, or the empty string
     */
    private static function matchTimestamp(array $match, ?array $score): string
    {
        if ($score !== null) {
            $stamp = self::text($score['timestamp'] ?? null);
            if ($stamp !== '') {
                return $stamp;
            }
        }
        $liveAt = self::text($match['live_at'] ?? null);
        if ($liveAt !== '') {
            return $liveAt;
        }
        return self::text($match['scheduled_time'] ?? null);
    }

    /**
     * @return array<int, string>
     */
    private static function categories(array $match): array
    {
        $categories = [];
        foreach (['tour', 'draw', 'round_code'] as $field) {
            $value = self::text($match[$field] ?? null);
            if ($value !== '') {
                $categories[] = $value;
            }
        }
        return $categories;
    }

    /**
     * @param mixed $player
     */
    private static function playerName($player, string $fallback = ''): string
    {
        if (!is_array($player)) {
            return $fallback;
        }
        return self::text($player['name'] ?? null, $fallback);
    }

    /**
     * @param mixed $player
     */
    private static function playerLine($player, string $name): string
    {
        if (!is_array($player)) {
            return $name;
        }
        $notes = [];
        $country = self::text($player['country'] ?? null);
        if ($country !== '') {
            $notes[] = strtoupper($country);
        }
        // ranking is the current official singles position, never points or a seed.
        $ranking = $player['ranking'] ?? null;
        if (is_numeric($ranking)) {
            $notes[] = 'no. ' . (int) $ranking;
        }
        return $notes === [] ? $name : $name . ' (' . implode(', ', $notes) . ')';
    }

    private static function formatInstant(string $instant): string
    {
        $timestamp = strtotime($instant);
        if ($timestamp === false) {
            return $instant;
        }
        return gmdate('Y-m-d H:i', $timestamp) . ' UTC';
    }

    /**
     * @param array<string, string> $rows
     */
    private static function renderRows(array $rows): string
    {
        $html = '';
        foreach ($rows as $label => $value) {
            if ($value === '') {
                continue;
            }
            $html .= sprintf('<li><strong>%s:</strong> %s</li>', e((string) $label), e($value));
        }
        return '<ul>' . $html . '</ul>';
    }

    private static function suffix(string $value, string $separator): string
    {
        return $value === '' ? '' : $separator . $value;
    }

    /**
     * Every string field in this api is nullable and a null is a real state rather than
     * a gap, so read one to a string once and branch on emptiness.
     *
     * @param mixed $value
     */
    private static function text($value, string $fallback = ''): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return $fallback;
    }
}
