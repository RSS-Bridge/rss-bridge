<?php

declare(strict_types=1);

class FLZBridge extends BridgeAbstract
{
    const NAME = 'Fränkische Landeszeitung';
    const URI = 'https://www.flz.de/';
    const DESCRIPTION = 'Westmittelfranken nach Ressort und Ort, ohne PR-Veröffentlichungen. FLZ+-Volltext mit hinterlegten Zugangsdaten';
    const MAINTAINER = 'meyerjom';
    const CACHE_TIMEOUT = 1800;

    // Optional FLZ+ subscription, set in config.ini.php: [FLZBridge] username = "…", password = "…"
    const CONFIGURATION = [
        'username' => ['required' => false],
        'password' => ['required' => false],
    ];

    // The context name is part of existing feed URLs and must not change.
    const CONTEXT = 'Feed zusammenstellen';

    const PARAMETERS = [
        self::CONTEXT => [
            'r_blaulicht' => ['name' => 'Ressort: Blaulicht', 'type' => 'checkbox'],
            'r_bayern' => ['name' => 'Ressort: Bayern', 'type' => 'checkbox'],
            'r_deutschlandunddiewelt' => ['name' => 'Ressort: Deutschland & die Welt', 'type' => 'checkbox'],
            'r_sportausallerwelt' => ['name' => 'Ressort: Sport aus aller Welt', 'type' => 'checkbox'],
            'r_wirtschaft' => ['name' => 'Ressort: Wirtschaft', 'type' => 'checkbox'],
            'r_gastro' => ['name' => 'Ressort: Gastro', 'type' => 'checkbox'],
            'r_ratgeber' => ['name' => 'Ressort: Ratgeber', 'type' => 'checkbox'],
            'g_stadt_ansbach' => ['name' => 'Orte: Stadt Ansbach', 'type' => 'checkbox'],
            'g_lk_ansbach' => ['name' => 'Orte: LK Ansbach', 'type' => 'checkbox', 'title' => '55 Orte'],
            'g_lk_nea' => ['name' => 'Orte: LK NEA-Bad Windsheim', 'type' => 'checkbox', 'title' => '35 Orte'],
            'g_umland' => ['name' => 'Orte: Umland', 'type' => 'checkbox', 'title' => '13 Orte, u. a. Erlangen, Fürth, Nürnberg'],
            'more_places' => ['name' => 'Einzelne Orte', 'title' => 'Kommagetrennt', 'exampleValue' => 'Insingen, Sugenheim'],
            'filter_ads' => ['name' => 'PR-Veröffentlichungen ausfiltern', 'type' => 'checkbox', 'defaultValue' => 'checked'],
            'fulltext' => ['name' => 'Volltext statt Teaser', 'type' => 'checkbox', 'title' => 'FLZ+ braucht Zugangsdaten in der Bridge-Konfiguration'],
            'limit' => self::LIMIT,
        ],
    ];

    // Municipalities of the two districts; "Umland" collects the rest.
    const PLACES = [
        'g_stadt_ansbach' => ['Ansbach'],
        'g_lk_ansbach' => [
            'Adelshofen', 'Arberg', 'Aurach', 'Bechhofen', 'Bruckberg', 'Buch am Wald', 'Burgoberbach', 'Burk', 'Colmberg',
            'Dentlein am Forst', 'Diebach', 'Dietenhofen', 'Dinkelsbühl', 'Dombühl', 'Dürrwangen', 'Ehingen', 'Feuchtwangen',
            'Flachslanden', 'Gebsattel', 'Gerolfingen', 'Geslau', 'Heilsbronn', 'Herrieden', 'Insingen', 'Langfurth', 'Lehrberg',
            'Leutershausen', 'Lichtenau', 'Merkendorf', 'Mitteleschenbach', 'Mönchsroth', 'Neuendettelsau', 'Neusitz',
            'Oberdachstetten', 'Ohrenbach', 'Petersaurach', 'Rothenburg', 'Rügland', 'Sachsen bei Ansbach', 'Schillingsfürst',
            'Schnelldorf', 'Schopfloch', 'Steinsfeld', 'Wassertrüdingen', 'Weidenbach', 'Weihenzell', 'Weiltingen', 'Wettringen',
            'Wieseth', 'Wilburgstetten', 'Windelsbach', 'Windsbach', 'Wittelshofen', 'Wolframs-Eschenbach', 'Wörnitz',
        ],
        'g_lk_nea' => [
            'Bad Windsheim', 'Baudenbach', 'Burgbernheim', 'Burghaslach', 'Dachsbach', 'Diespeck', 'Dietersheim', 'Emskirchen',
            'Ergersheim', 'Gallmersgarten', 'Gerhardshofen', 'Gollhofen', 'Gutenstetten', 'Hagenbüchach', 'Hemmersheim',
            'Illesheim', 'Ipsheim', 'Langenfeld', 'Markt Bibart', 'Markt Erlbach', 'Markt Nordheim', 'Markt Taschendorf',
            'Marktbergel', 'Münchsteinach', 'Neuhof', 'Oberickelsheim', 'Oberscheinfeld', 'Scheinfeld', 'Simmershofen',
            'Sugenheim', 'Trautskirchen', 'Uehlfeld', 'Uffenheim', 'Weigenheim', 'Wilhelmsdorf',
        ],
        'g_umland' => [
            'Creglingen', 'Erlangen', 'Fürth', 'Gunzenhausen', 'München', 'Neustadt/Aisch', 'Nürnberg', 'Roth', 'Schwabach',
            'Treuchtlingen', 'Weißenburg', 'Wilhermsdorf', 'Würzburg',
        ],
    ];

    const AD_FEED = '/sonderthemen';
    const RSS_ENDPOINT = 'https://www.flz.de/api/content/public/rss?path=';
    const SSO_URI = 'https://sso.flz.de/auth/authorize?client_id=pscontent-portal&redirect_uri=https://www.flz.de/abo_service';
    const LOGON_STATUS_URI = 'https://sso.flz.de/auth/logonstatus';

    // The RSS endpoint answers HTTP 429 after about 27 quick requests.
    const MAX_FEED_REQUESTS = 25;
    const MAX_ARTICLE_REQUESTS = 10;

    private string $cookie = '';
    private ?string $loginProblem = null;
    private int $feedRequests = 0;
    private int $articleRequests = 0;
    private bool $throttled = false;
    private int $postponedFeeds = 0;
    private int $postponedArticles = 0;

    public function collectData()
    {
        $feeds = $this->getFeeds();
        $filterAds = (bool)$this->getInput('filter_ads');
        $states = $this->refreshFeeds(array_merge($filterAds ? [self::AD_FEED] : [], array_keys($feeds)));
        $ads = $filterAds ? $this->collectAds($states[self::AD_FEED]['items'] ?? []) : [];

        if (!array_filter(array_intersect_key($states, $feeds), fn($state) => $state['ok'] ?? false)) {
            throwServerException(sprintf('None of the %d selected flz.de feeds could be fetched', count($feeds)));
        }

        // Articles appear in several feeds; every origin becomes a category.
        $items = [];
        foreach ($feeds as $path => $label) {
            foreach ($states[$path]['items'] ?? [] as $item) {
                if (!isset($ads[$item['uid']])) {
                    $items[$item['uid']] ??= $item + ['categories' => []];
                    $items[$item['uid']]['categories'][] = $label;
                }
            }
        }
        usort($items, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        $limit = (int)$this->getInput('limit');

        foreach (array_slice($items, 0, $limit > 0 ? $limit : 50) as $item) {
            if ($this->getInput('fulltext')) {
                $item['content'] = $this->fullContent($item);
            }
            if ($item['content'] !== null) {
                $this->items[] = $item;
            }
        }

        if ($this->throttled) {
            $this->logger->warning(sprintf('FLZ: throttled by flz.de (HTTP 429) after %d feed requests', $this->feedRequests));
        }
        if ($this->postponedFeeds || $this->postponedArticles) {
            $this->logger->info(sprintf(
                'FLZ: %d due feeds and %d articles wait for the next refresh',
                $this->postponedFeeds,
                $this->postponedArticles
            ));
        }
    }

    public function getName()
    {
        $fields = array_filter(array_keys(self::PARAMETERS[self::CONTEXT]), fn($field) => preg_match('~^[rg]_~', $field) && $this->getInput($field));
        $names = array_merge(array_map(fn($field) => $this->label($field), $fields), $this->individualPlaces());
        return $names ? self::NAME . ': ' . implode(' · ', $names) : parent::getName();
    }

    // Feeds to show as path => category. Without a selection all sections are used.
    private function getFeeds(bool $allSections = false): array
    {
        $feeds = [];
        $places = $this->individualPlaces();
        foreach (array_keys(self::PARAMETERS[self::CONTEXT]) as $field) {
            if (str_starts_with($field, 'r_') && ($allSections || $this->getInput($field))) {
                $feeds['/' . substr($field, 2)] = $this->label($field);
            } elseif (isset(self::PLACES[$field]) && $this->getInput($field)) {
                $places = array_merge($places, self::PLACES[$field]);
            }
        }
        foreach ($places as $place) {
            $feeds['/orte/' . str_replace(['/', ' '], ['_slash_', '-'], $place)] = $place;
        }
        return $feeds ?: $this->getFeeds(true);
    }

    private function individualPlaces(): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', (string)$this->getInput('more_places'))))));
    }

    // "Ressort: Blaulicht" becomes "Blaulicht".
    private function label(string $field): string
    {
        return explode(': ', self::PARAMETERS[self::CONTEXT][$field]['name'], 2)[1];
    }

    /**
     * Feeds are cached and fetched again after an eighth of the average gap between their
     * articles, within 30 minutes and 3 hours, the most overdue first. Most place feeds
     * get an article a week or less.
     */
    private function refreshFeeds(array $paths): array
    {
        $states = [];
        $overdue = [];
        foreach ($paths as $path) {
            $states[$path] = $this->loadCacheValue('feed_' . md5($path));
            $overdue[$path] = $states[$path] ? (time() - $states[$path]['fetched']) / $this->interval($states[$path]['items']) : INF;
        }
        arsort($overdue);

        foreach ($overdue as $path => $ratio) {
            if ($ratio < 1) {
                break;
            }
            if ($this->throttled || $this->feedRequests >= self::MAX_FEED_REQUESTS) {
                $this->postponedFeeds++;
                continue;
            }
            $items = $this->collectFeed($path);
            if ($this->throttled) {
                $this->postponedFeeds++;
                continue;
            }
            $old = $states[$path];
            $states[$path] = ['fetched' => time(), 'ok' => $items !== null || ($old['ok'] ?? false), 'items' => $items ?? $old['items'] ?? []];
            $this->saveCacheValue('feed_' . md5($path), $states[$path], 3 * 86400);
        }
        return $states;
    }

    private function interval(array $items): float
    {
        $times = array_filter(array_column($items, 'timestamp'));
        $gap = $times ? (time() - min($times)) / count($times) : INF;
        return min(3 * 3600, max(1800, $gap / 8));
    }

    private function collectFeed(string $path): ?array
    {
        $this->feedRequests++;
        try {
            $feed = (new FeedParser())->parseFeed($this->fetch(self::RSS_ENDPOINT . rawurlencode(base64_encode($path))));
        } catch (Exception $e) {
            if ($e instanceof HttpException && $e->getCode() === 429) {
                $this->throttled = true;
            } else {
                $this->logger->info(sprintf('FLZ: feed %s not available: %s', $path, $e->getMessage()));
            }
            return null;
        }

        $items = [];
        foreach ($feed['items'] as $entry) {
            if (trim($entry['uri']) === '' || trim($entry['title']) === '') {
                continue;
            }
            $image = $entry['enclosures'][0] ?? '';
            $teaser = trim($entry['content']);
            $items[] = array_filter([
                'uri' => trim($entry['uri']),
                // Links start with the title, which may change, and end with the article id.
                'uid' => preg_match('~/cnt-id-([\w-]+)~', $entry['uri'], $match) ? $match[1] : trim($entry['uri']),
                'title' => trim($entry['title']),
                'content' => $this->withImage($image, $teaser === '' ? '' : '<p>' . e($teaser) . '</p>'),
                'enclosures' => $image === '' ? null : [$image],
                'timestamp' => $entry['timestamp'] ?: null,
            ], fn($value) => $value !== null);
        }
        return $items;
    }

    // Advertorials are listed in the feed sonderthemen or labelled on the overview pages.
    private function collectAds(array $adFeedItems): array
    {
        $ads = array_fill_keys(array_column($adFeedItems, 'uid'), true);
        foreach ([self::URI, self::URI . 'orte'] as $url) {
            try {
                $dom = str_get_html($this->fetch($url));
            } catch (Exception $e) {
                $this->logger->info(sprintf('FLZ: overview %s not available: %s', $url, $e->getMessage()));
                continue;
            }
            foreach ($dom->find('*') as $node) {
                if (str_ends_with($node->tag, '-article-preview') && preg_match('~cat-ad-labeling|PR-Ver~', $node->innertext)) {
                    $link = $node->find('a[href*=/cnt-id-]', 0);
                    if ($link && preg_match('~/cnt-id-([\w-]+)~', (string)$link->href, $match)) {
                        $ads[$match[1]] = true;
                    }
                }
            }
        }
        return $ads;
    }

    /**
     * Full text from the article page, cached for three days. A teaser gets a notice with
     * the reason and is tried again after an hour. Returns null for an article that has to
     * wait for the next refresh, so that it does not show up as a teaser first.
     */
    private function fullContent(array $item): ?string
    {
        $key = 'article_' . md5($item['uri'] . '|' . ($item['timestamp'] ?? ''));
        $image = $item['enclosures'][0] ?? '';
        $cached = $this->loadCacheValue($key);
        if ($cached !== null) {
            return is_array($cached) ? $cached['teaser'] : $this->withImage($image, $cached);
        }
        if ($this->articleRequests++ >= self::MAX_ARTICLE_REQUESTS) {
            $this->postponedArticles++;
            return null;
        }
        $this->loginProblem ??= $this->login();

        try {
            $html = $this->fetch($item['uri']);
            $body = '';
            foreach (str_get_html($html)->find('p.article-text, h3.article-subtitle-h3') as $element) {
                $text = trim(preg_replace('~\s+~u', ' ', html_entity_decode($element->plaintext, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
                $body .= $text === '' ? '' : sprintf('<%1$s>%2$s</%1$s>', $element->tag, e($text));
            }
        } catch (Exception $e) {
            $this->logger->info(sprintf('FLZ: article %s not available: %s', $item['uri'], $e->getMessage()));
            return $this->teaser($key, 'Volltext konnte nicht abgerufen werden.', $item['content']);
        }
        if ($body === '') {
            return $this->teaser($key, 'Volltext auf der Artikelseite nicht gefunden.', $item['content']);
        }
        // Without entitlement the page carries these markers; free articles have none.
        if (!preg_match('~payWallContentPart|pw2-login-hint|"restricted":true~', $html)) {
            $this->saveCacheValue($key, $body, 3 * 86400);
            return $this->withImage($image, $body);
        }
        $reason = $this->loginProblem ?: 'Die Anmeldung ist aktiv, flz.de gibt den Artikel aber nicht frei (Abo prüfen).';
        return $this->teaser($key, 'FLZ+-Artikel nur als Teaser: ' . $reason, $this->withImage($image, $body));
    }

    private function teaser(string $key, string $notice, string $content): string
    {
        $teaser = '<p><strong>[FLZ-Bridge] ' . e($notice) . '</strong></p>' . $content;
        $this->saveCacheValue($key, ['teaser' => $teaser], 3600);
        return $teaser;
    }

    private function withImage(string $image, string $body): string
    {
        return $image === '' ? $body : '<p><img src="' . e($image) . '" alt=""></p>' . $body;
    }

    /**
     * Logs in at the SSO and returns why FLZ+ articles stay teasers, or '' when logged in.
     * The session is not cached: the Docker image serves the cache folder as static files.
     */
    private function login(): string
    {
        $username = (string)$this->getOption('username');
        $password = (string)$this->getOption('password');
        if ($username === '' || $password === '') {
            return 'Keine FLZ+-Zugangsdaten in der Bridge-Konfiguration hinterlegt.';
        }
        // After a rejected login wait 6 hours, so that wrong credentials do not lock the account.
        if ($this->loadCacheValue('login_failed') !== null) {
            return 'Die letzte FLZ+-Anmeldung schlug fehl, neuer Versuch nach 6 Stunden. Zugangsdaten und Abo prüfen.';
        }

        try {
            // The form sets four cookies and posts to itself; the redirects after the post
            // establish the session on www.flz.de.
            $jar = $this->cookies($this->fetchWithoutRedirect(self::SSO_URI, []));
            $response = $this->fetchWithoutRedirect(self::SSO_URI, [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://sso.flz.de',
                'Referer: ' . self::SSO_URI,
                'Cookie: ' . $jar,
            ], [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([
                'username' => $username,
                'password' => $password,
                'remember_me' => '1',
                'logon' => 'Anmeldung',
            ])]);
            $url = self::SSO_URI;
            for ($hops = 0;; $hops++) {
                $jar = $this->cookies($response, $jar);
                $location = (string)$response->getHeader('location');
                if ($location === '' || $hops === 5 || !$this->isFlzHost($url = urljoin($url, $location))) {
                    break;
                }
                $response = $this->fetchWithoutRedirect($url, ['Cookie: ' . $jar]);
            }
            // The status answers 2000 when logged in and 2100 when not.
            $status = Json::decode(getContents(self::LOGON_STATUS_URI, ['Accept: application/json', 'Cookie: ' . $jar]));
            if (!isset($status['code'])) {
                throw new \Exception('Unexpected answer from the logon status');
            }
        } catch (Exception $e) {
            $this->logger->warning('FLZ: login service failed: ' . $e->getMessage());
            return 'Der FLZ-Anmeldedienst (sso.flz.de) war nicht erreichbar oder lieferte einen Fehler.';
        }

        if ((int)$status['code'] === 2000) {
            $this->cookie = $jar;
            return '';
        }
        $reason = (string)($status['message'] ?? '') ?: 'Sitzung nicht angemeldet';
        $this->logger->warning(sprintf('FLZ: login failed (%s), pausing for 6 hours', $reason));
        $this->saveCacheValue('login_failed', time(), 6 * 3600);
        return sprintf('FLZ+-Anmeldung fehlgeschlagen (%s). Zugangsdaten und Abo prüfen.', $reason);
    }

    // Since #5130 getContents() throws on redirects; the login needs their cookies.
    private function fetchWithoutRedirect(string $url, array $headers, array $curlOptions = []): Response
    {
        try {
            return getContents($url, $headers, [CURLOPT_FOLLOWLOCATION => false] + $curlOptions, true);
        } catch (HttpException $e) {
            if ($e->getCode() >= 300 && $e->getCode() < 400 && isset($e->response)) {
                return $e->response;
            }
            throw $e;
        }
    }

    // getHeader() without true returns only the last of several Set-Cookie headers.
    private function cookies(Response $response, string $jar = ''): string
    {
        $cookies = [];
        foreach (array_filter(explode('; ', $jar)) as $pair) {
            [$name, $value] = explode('=', $pair, 2);
            $cookies[$name] = $value;
        }
        foreach ((array)$response->getHeader('set-cookie', true) as $line) {
            [$name, $value] = array_pad(explode('=', explode(';', (string)$line)[0], 2), 2, '');
            if ($value !== '' && $value !== 'deleted') {
                $cookies[trim($name)] = $value;
            }
        }
        return implode('; ', array_map(fn($name, $value) => $name . '=' . $value, array_keys($cookies), $cookies));
    }

    private function fetch(string $url): string
    {
        $headers = ['Accept-Language: de-DE,de;q=0.9', 'Referer: ' . self::URI];
        // The session cookie only goes to flz.de; curl drops it on redirects to other hosts.
        if ($this->cookie !== '' && $this->isFlzHost($url)) {
            $headers[] = 'Cookie: ' . $this->cookie;
        }
        return getContents($url, $headers);
    }

    private function isFlzHost(string $url): bool
    {
        $host = Url::validate($url) ? strtolower(Url::fromString($url)->getHost()) : '';
        return $host === 'flz.de' || str_ends_with($host, '.flz.de');
    }
}
