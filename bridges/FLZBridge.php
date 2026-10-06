<?php

class FLZBridge extends BridgeAbstract
{
    const NAME = 'Fränkische Landeszeitung';
    const URI = 'https://www.flz.de/';
    const DESCRIPTION = 'Westmittelfranken nach Ressort und Ort, ohne PR-Veröffentlichungen. FLZ+-Volltext mit hinterlegten Zugangsdaten';
    const MAINTAINER = 'meyerjom';
    const CACHE_TIMEOUT = 1800;

    /**
     * Optional FLZ+ credentials, used to get the full text of paywalled
     * articles. They belong in the config.ini.php of the host and never in a
     * feed URL:
     *
     *   [FLZBridge]
     *   username = "..."
     *   password = "..."
     *
     * Without them the bridge works normally and FLZ+ articles stay teasers.
     */
    const CONFIGURATION = [
        'username' => [
            'required' => false,
        ],
        'password' => [
            'required' => false,
        ],
    ];

    /**
     * The form is German like the site and its readers. The context name is
     * part of existing feed URLs: renaming it would make RSS-Bridge ignore
     * every input of those URLs without an error.
     */
    const CONTEXT = 'Feed zusammenstellen';

    const PARAMETERS = [
        self::CONTEXT => [
            'r_blaulicht' => [
                'name' => 'Ressort: Blaulicht',
                'type' => 'checkbox',
            ],
            'r_bayern' => [
                'name' => 'Ressort: Bayern',
                'type' => 'checkbox',
            ],
            'r_deutschlandunddiewelt' => [
                'name' => 'Ressort: Deutschland & die Welt',
                'type' => 'checkbox',
            ],
            'r_sportausallerwelt' => [
                'name' => 'Ressort: Sport aus aller Welt',
                'type' => 'checkbox',
            ],
            'r_wirtschaft' => [
                'name' => 'Ressort: Wirtschaft',
                'type' => 'checkbox',
            ],
            'r_gastro' => [
                'name' => 'Ressort: Gastro',
                'type' => 'checkbox',
            ],
            'r_ratgeber' => [
                'name' => 'Ressort: Ratgeber',
                'type' => 'checkbox',
            ],
            'g_stadt_ansbach' => [
                'name' => 'Orte: Stadt Ansbach',
                'type' => 'checkbox',
                'title' => '1 Ort',
            ],
            'g_lk_ansbach' => [
                'name' => 'Orte: LK Ansbach',
                'type' => 'checkbox',
                'title' => '55 Orte: Adelshofen, Arberg, Aurach und weitere',
            ],
            'g_lk_nea' => [
                'name' => 'Orte: LK NEA-Bad Windsheim',
                'type' => 'checkbox',
                'title' => '35 Orte: Bad Windsheim, Baudenbach, Burgbernheim und weitere',
            ],
            'g_umland' => [
                'name' => 'Orte: Umland',
                'type' => 'checkbox',
                'title' => '13 Orte: Creglingen, Erlangen, Fürth und weitere',
            ],
            'more_places' => [
                'name' => 'Einzelne Orte',
                'type' => 'text',
                'required' => false,
                'title' => 'Kommagetrennt, wenn nicht der ganze Landkreis gewünscht ist',
                'exampleValue' => 'Insingen, Sugenheim',
            ],
            'filter_ads' => [
                'name' => 'PR-Veröffentlichungen ausfiltern',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Entfernt bezahlte Sonderveröffentlichungen, in Wirtschaft und Gastro rund 38 Prozent der Beiträge',
            ],
            'fulltext' => [
                'name' => 'Volltext statt Teaser',
                'type' => 'checkbox',
                'title' => 'Ruft jeden Artikel einzeln ab. FLZ+-Artikel brauchen Zugangsdaten in der Bridge-Konfiguration, sonst bleiben sie Teaser',
            ],
            'limit' => self::LIMIT,
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://www.flz.de/blaulicht' => [
            'context' => self::CONTEXT,
            'r_blaulicht' => 'on',
        ],
        'https://flz.de/orte/bad-windsheim' => [
            'context' => self::CONTEXT,
            'more_places' => 'Bad Windsheim',
        ],
        'https://www.flz.de/orte/Neustadt_slash_Aisch' => [
            'context' => self::CONTEXT,
            'more_places' => 'Neustadt/Aisch',
        ],
    ];

    /**
     * Sections with a working feed, as checkbox => path.
     * There is no combined feed: the path / answers with HTTP 500.
     */
    const SECTIONS = [
        'r_blaulicht' => 'blaulicht',
        'r_bayern' => 'bayern',
        'r_deutschlandunddiewelt' => 'deutschlandunddiewelt',
        'r_sportausallerwelt' => 'sportausallerwelt',
        'r_wirtschaft' => 'wirtschaft',
        'r_gastro' => 'gastro',
        'r_ratgeber' => 'ratgeber',
    ];

    /**
     * Places per selection group. The assignment follows the official
     * municipality lists of the two districts; "Umland" collects the rest.
     */
    const PLACES = [
        'g_stadt_ansbach' => [
            'Ansbach',
        ],
        'g_lk_ansbach' => [
            'Adelshofen',
            'Arberg',
            'Aurach',
            'Bechhofen',
            'Bruckberg',
            'Buch am Wald',
            'Burgoberbach',
            'Burk',
            'Colmberg',
            'Dentlein am Forst',
            'Diebach',
            'Dietenhofen',
            'Dinkelsbühl',
            'Dombühl',
            'Dürrwangen',
            'Ehingen',
            'Feuchtwangen',
            'Flachslanden',
            'Gebsattel',
            'Gerolfingen',
            'Geslau',
            'Heilsbronn',
            'Herrieden',
            'Insingen',
            'Langfurth',
            'Lehrberg',
            'Leutershausen',
            'Lichtenau',
            'Merkendorf',
            'Mitteleschenbach',
            'Mönchsroth',
            'Neuendettelsau',
            'Neusitz',
            'Oberdachstetten',
            'Ohrenbach',
            'Petersaurach',
            'Rothenburg',
            'Rügland',
            'Sachsen bei Ansbach',
            'Schillingsfürst',
            'Schnelldorf',
            'Schopfloch',
            'Steinsfeld',
            'Wassertrüdingen',
            'Weidenbach',
            'Weihenzell',
            'Weiltingen',
            'Wettringen',
            'Wieseth',
            'Wilburgstetten',
            'Windelsbach',
            'Windsbach',
            'Wittelshofen',
            'Wolframs-Eschenbach',
            'Wörnitz',
        ],
        'g_lk_nea' => [
            'Bad Windsheim',
            'Baudenbach',
            'Burgbernheim',
            'Burghaslach',
            'Dachsbach',
            'Diespeck',
            'Dietersheim',
            'Emskirchen',
            'Ergersheim',
            'Gallmersgarten',
            'Gerhardshofen',
            'Gollhofen',
            'Gutenstetten',
            'Hagenbüchach',
            'Hemmersheim',
            'Illesheim',
            'Ipsheim',
            'Langenfeld',
            'Markt Bibart',
            'Markt Erlbach',
            'Markt Nordheim',
            'Markt Taschendorf',
            'Marktbergel',
            'Münchsteinach',
            'Neuhof',
            'Oberickelsheim',
            'Oberscheinfeld',
            'Scheinfeld',
            'Simmershofen',
            'Sugenheim',
            'Trautskirchen',
            'Uehlfeld',
            'Uffenheim',
            'Weigenheim',
            'Wilhelmsdorf',
        ],
        'g_umland' => [
            'Creglingen',
            'Erlangen',
            'Fürth',
            'Gunzenhausen',
            'München',
            'Neustadt/Aisch',
            'Nürnberg',
            'Roth',
            'Schwabach',
            'Treuchtlingen',
            'Weißenburg',
            'Wilhermsdorf',
            'Würzburg',
        ],
    ];

    /**
     * Purely promotional category, used as a deny list. The other way round
     * does not work: most of ratgeber is editorial.
     */
    const AD_SECTION = '/sonderthemen';

    const RSS_ENDPOINT = 'https://www.flz.de/api/content/public/rss?path=';
    const SSO_URI = 'https://sso.flz.de/auth/authorize?client_id=pscontent-portal&redirect_uri=https://www.flz.de/abo_service';
    const LOGON_STATUS_URI = 'https://sso.flz.de/auth/logonstatus';

    /**
     * The site throttles the RSS endpoint at about 6.5 requests per second
     * with HTTP 429 and sends no Retry-After header. 5 requests per second
     * ran 64 feeds in a row without a single 429.
     */
    const REQUEST_INTERVAL_S = 0.2;
    const RATE_LIMIT_RETRIES = 3;
    const RATE_LIMIT_BACKOFF_S = 1;

    /**
     * The response should be complete after DEADLINE_S, ahead of the 45 s
     * fastcgi_read_timeout of the Docker image: an incomplete feed is better
     * than a timeout. A request is only started with at least MIN_REQUEST_S
     * left, and its curl timeout is capped to the time that is left. Feeds
     * may use FEED_SHARE of the time, so that full text still gets some.
     */
    const DEADLINE_S = 42;
    const MIN_REQUEST_S = 3;
    const FEED_SHARE = 0.8;

    /**
     * Complete article texts are kept this long, so that a refresh only
     * fetches the article pages it has not seen yet.
     */
    const ARTICLE_CACHE_TTL = 21600;

    /**
     * After a failed login, no new attempt is made for this many seconds.
     * The feed is fetched regularly; without the pause, wrong credentials
     * would cause a login attempt at every refresh and might get the account
     * locked.
     */
    const LOGIN_LOCK_TTL = 21600;

    private string $cookie = '';

    /** Why FLZ+ articles stay teasers. Empty if the login is confirmed. */
    private string $loginProblem = '';

    private float $started = 0.0;
    private float $runtime = 0.0;
    private float $lastRequest = 0.0;

    /** Feeds that stayed throttled or were skipped for lack of time. */
    private array $skippedFeeds = [];

    public function collectData()
    {
        $this->started = microtime(true);
        $this->runtime = $this->availableRuntime();

        $fulltext = (bool)$this->getInput('fulltext');
        if ($fulltext) {
            $this->login();
        }

        $ads = $this->getInput('filter_ads') ? $this->collectAds() : [];
        $feeds = $this->getFeeds();

        // An article usually appears in several feeds. Instead of dropping
        // the duplicate, its origin is added as another category.
        $items = [];
        $fetched = 0;
        foreach ($feeds as $path => $label) {
            $entries = $this->collectFeed($path);
            if ($entries === null) {
                continue;
            }
            $fetched++;
            foreach ($entries as $item) {
                $id = $item['uid'];
                if (isset($ads[$id])) {
                    continue;
                }
                if (isset($items[$id])) {
                    $items[$id]['categories'][] = $label;
                    continue;
                }
                $item['categories'] = [$label];
                $items[$id] = $item;
            }
        }

        if ($fetched === 0) {
            // An empty feed would look like a quiet day on flz.de.
            throwServerException(sprintf('None of the %d selected flz.de feeds could be fetched', count($feeds)));
        }

        usort($items, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        $limit = (int)$this->getInput('limit');
        $items = array_slice($items, 0, $limit > 0 ? $limit : 50);

        foreach ($items as $item) {
            if ($fulltext) {
                $item['content'] = $this->collectFullContent($item);
            }
            $this->items[] = $item;
        }

        if ($this->skippedFeeds !== []) {
            $this->logger->warning(sprintf(
                'FLZ: %d of %d feeds are incomplete (throttled, or out of time after %.1f s): %s',
                count($this->skippedFeeds),
                count($feeds),
                microtime(true) - $this->started,
                implode(', ', $this->skippedFeeds)
            ));
        }
    }

    public function getName()
    {
        $fields = $this->checkedFields(array_merge(array_keys(self::SECTIONS), array_keys(self::PLACES)));
        $names = array_merge(array_map(fn($field) => $this->fieldLabel($field), $fields), $this->getIndividualPlaces());
        if ($names === []) {
            return parent::getName();
        }
        return self::NAME . ': ' . implode(' · ', $names);
    }

    /**
     * Links to the page of the section or place if the feed shows exactly
     * one of them, in the lower case spelling the site uses for its links.
     */
    public function getURI()
    {
        $feeds = $this->getFeeds();
        if (count($feeds) !== 1) {
            return parent::getURI();
        }
        $segments = explode('/', trim((string)array_key_first($feeds), '/'));
        return self::URI . implode('/', array_map(fn($segment) => rawurlencode(mb_strtolower($segment)), $segments));
    }

    public function detectParameters($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        if (!in_array($host, ['flz.de', 'www.flz.de'], true) || !is_string($path)) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        if (count($segments) === 1) {
            $field = array_search(strtolower($segments[0]), self::SECTIONS, true);
            return $field === false ? null : ['context' => self::CONTEXT, $field => 'on'];
        }
        if (count($segments) === 2 && $segments[0] === 'orte' && $segments[1] !== '') {
            return ['context' => self::CONTEXT, 'more_places' => $this->placeFromPath(rawurldecode($segments[1]))];
        }
        return null;
    }

    /**
     * Turns the form into the feeds to fetch, as path => display name. The
     * name ends up as a category on the article. If nothing is selected, all
     * sections are used, so that the feed does not stay empty unnoticed.
     */
    private function getFeeds(): array
    {
        $sections = $this->checkedFields(array_keys(self::SECTIONS));
        $places = $this->getIndividualPlaces();
        foreach ($this->checkedFields(array_keys(self::PLACES)) as $group) {
            $places = array_merge($places, self::PLACES[$group]);
        }
        if ($sections === [] && $places === []) {
            $sections = array_keys(self::SECTIONS);
        }

        $feeds = [];
        foreach ($sections as $field) {
            $feeds['/' . self::SECTIONS[$field]] = $this->fieldLabel($field);
        }
        foreach ($places as $place) {
            $feeds[$this->placePath($place)] = $place;
        }
        return $feeds;
    }

    private function checkedFields(array $fields): array
    {
        return array_values(array_filter($fields, fn($field) => (bool)$this->getInput($field)));
    }

    private function getIndividualPlaces(): array
    {
        $places = [];
        foreach (explode(',', (string)$this->getInput('more_places')) as $place) {
            $place = trim($place);
            if ($place !== '') {
                $places[] = $this->canonicalPlace($place);
            }
        }
        return array_values(array_unique($places));
    }

    /**
     * Gives a known place the spelling of the place lists, so that
     * "insingen" and "Insingen" are the same feed and the same category.
     */
    private function canonicalPlace(string $name): string
    {
        $normalize = fn(string $place) => mb_strtolower(str_replace('-', ' ', $place));
        foreach (self::PLACES as $places) {
            foreach ($places as $place) {
                if ($normalize($place) === $normalize($name)) {
                    return $place;
                }
            }
        }
        return $name;
    }

    /**
     * Path of a place feed: "Neustadt/Aisch" becomes "/orte/Neustadt_slash_Aisch".
     */
    private function placePath(string $place): string
    {
        return '/orte/' . str_replace(['/', ' '], ['_slash_', '-'], $place);
    }

    /**
     * Reverses placePath() for the last segment of a place URL. Known places
     * get their proper spelling back, which the site loses by lowercasing
     * its own links.
     */
    private function placeFromPath(string $slug): string
    {
        return $this->canonicalPlace(str_replace(['_slash_', '-'], ['/', ' '], $slug));
    }

    /**
     * Takes the display name from PARAMETERS, so that labels live in one
     * place: "Ressort: Blaulicht" becomes "Blaulicht".
     */
    private function fieldLabel(string $field): string
    {
        $name = self::PARAMETERS[self::CONTEXT][$field]['name'] ?? $field;
        return explode(': ', $name, 2)[1] ?? $name;
    }

    /**
     * @return array|null Items, or null if the feed could not be fetched
     */
    private function collectFeed(string $path): ?array
    {
        $xml = $this->fetchFeed($path);
        if ($xml === null) {
            return null;
        }
        try {
            $feed = (new FeedParser())->parseFeed($xml);
        } catch (Exception $e) {
            $this->logger->info(sprintf('FLZ: feed %s is not valid: %s', $path, $e->getMessage()));
            return null;
        }

        $items = [];
        foreach ($feed['items'] as $entry) {
            $uri = trim($entry['uri']);
            $title = trim($entry['title']);
            // The site occasionally delivers entries without a title, which
            // are useless as feed items.
            if ($uri === '' || $title === '') {
                continue;
            }
            $image = $entry['enclosures'][0] ?? '';
            // The teaser is plain text.
            $teaser = trim($entry['content']);
            $item = [
                'uri' => $uri,
                'uid' => $this->articleId($uri),
                'title' => $title,
                'content' => $this->buildContent($image, $teaser === '' ? '' : '<p>' . e($teaser) . '</p>'),
            ];
            if ($image !== '') {
                $item['enclosures'] = [$image];
            }
            if ($entry['timestamp']) {
                $item['timestamp'] = $entry['timestamp'];
            }
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Fetches a feed. On HTTP 429 it retries with growing waits (1 s, 2 s,
     * 4 s) as long as the time allows. Any other error is only logged: a
     * single unknown place must not break the whole feed.
     *
     * @return string|null Raw XML, or null if the feed did not arrive
     */
    private function fetchFeed(string $path): ?string
    {
        $url = self::RSS_ENDPOINT . rawurlencode(base64_encode($path));
        for ($attempt = 0; $this->hasTimeLeft(self::FEED_SHARE); $attempt++) {
            try {
                return $this->fetch($url);
            } catch (Exception $e) {
                $throttled = $e instanceof HttpException && $e->getCode() === 429;
                if (!$throttled) {
                    $this->logger->info(sprintf('FLZ: feed %s not available: %s', $path, $e->getMessage()));
                    return null;
                }
            }
            $backoff = self::RATE_LIMIT_BACKOFF_S * 2 ** $attempt;
            if ($attempt === self::RATE_LIMIT_RETRIES || $this->secondsLeft(self::FEED_SHARE) - $backoff < self::MIN_REQUEST_S) {
                break;
            }
            sleep($backoff);
        }
        $this->skippedFeeds[] = $path;
        return null;
    }

    /**
     * Article links start with the title, which the site may still change,
     * and end with the id of the article. The id keeps an article
     * recognisable across feeds, overview pages and refreshes.
     */
    private function articleId(string $url): string
    {
        return preg_match('~/cnt-id-([\w-]+)~', $url, $match) ? $match[1] : $url;
    }

    /**
     * Advertorials can be recognised in two ways: they are listed in the
     * feed sonderthemen, and on the overview pages their preview carries
     * the class cat-ad-labeling or the label "PR-Veröffentlichung". The
     * article itself looks like editorial text.
     *
     * @return array Article id as key
     */
    private function collectAds(): array
    {
        $ads = [];
        foreach ($this->collectFeed(self::AD_SECTION) ?? [] as $item) {
            $ads[$item['uid']] = true;
        }

        foreach ([self::URI, self::URI . 'orte'] as $url) {
            if (!$this->hasTimeLeft(self::FEED_SHARE)) {
                break;
            }
            try {
                $dom = str_get_html($this->fetch($url));
            } catch (Exception $e) {
                $this->logger->info(sprintf('FLZ: overview %s not available: %s', $url, $e->getMessage()));
                continue;
            }

            foreach ($dom->find('*') as $node) {
                if (!str_ends_with($node->tag, '-article-preview')) {
                    continue;
                }
                $html = $node->innertext;
                if (!str_contains($html, 'cat-ad-labeling') && !str_contains($html, 'PR-Ver')) {
                    continue;
                }
                $link = $node->find('a[href*=/cnt-id-]', 0);
                if ($link) {
                    $ads[$this->articleId(urljoin(self::URI, $link->href))] = true;
                }
            }
        }
        return $ads;
    }

    /**
     * Replaces the teaser with the text of the article page. If the page
     * cannot be fetched or the paywall is still shown, the teaser stays and a
     * visible notice says so, so that a short text is not mistaken for a
     * short article.
     */
    private function collectFullContent(array $item): string
    {
        $image = $item['enclosures'][0] ?? '';

        // Only complete articles are cached, never a paywall teaser, so that
        // a repaired login takes effect at the next refresh. An article
        // republished with a new date gets a new key and is fetched again.
        $cacheKey = 'article_' . md5($item['uri'] . '|' . ($item['timestamp'] ?? ''));
        $body = $this->loadCacheValue($cacheKey);
        if (is_string($body) && $body !== '') {
            return $this->buildContent($image, $body);
        }

        if (!$this->hasTimeLeft()) {
            return $this->buildNotice('Volltext übersprungen, das Zeitbudget ist aufgebraucht.') . $item['content'];
        }

        try {
            $html = $this->fetch($item['uri']);
            $body = $this->extractBody(str_get_html($html));
        } catch (Exception $e) {
            $this->logger->info(sprintf('FLZ: article %s not available: %s', $item['uri'], $e->getMessage()));
            return $this->buildNotice('Volltext konnte nicht abgerufen werden (Artikelseite nicht erreichbar).') . $item['content'];
        }

        if ($body === '') {
            return $this->buildNotice('Volltext auf der Artikelseite nicht gefunden.') . $item['content'];
        }

        if (!$this->isPaywalled($html)) {
            $this->saveCacheValue($cacheKey, $body, self::ARTICLE_CACHE_TTL);
            return $this->buildContent($image, $body);
        }

        $reason = $this->loginProblem ?: 'Die Anmeldung ist aktiv, flz.de liefert den Artikel dennoch nicht frei (Abo abgelaufen oder Artikel nicht im Abo enthalten).';
        return $this->buildNotice('FLZ+-Artikel nur als Teaser: ' . $reason) . $this->buildContent($image, $body);
    }

    /**
     * Body text and subheadings are told apart by CSS class, not by text
     * length. The teaser block for related articles carries neither.
     */
    private function extractBody(simple_html_dom $dom): string
    {
        $body = '';
        foreach ($dom->find('p.article-text, h3.article-subtitle-h3') as $element) {
            $text = html_entity_decode($element->plaintext, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('~\s+~u', ' ', $text) ?? '');
            if ($text !== '') {
                $body .= sprintf('<%1$s>%2$s</%1$s>', $element->tag, e($text));
            }
        }
        return $body;
    }

    /**
     * Without entitlement the site renders a payWallContentPart into its page
     * data and the login hint pw2-login-hint, and the embedded JSON carries
     * "restricted":true. Free articles carry none of these, not even
     * anonymously.
     */
    private function isPaywalled(string $html): bool
    {
        return str_contains($html, 'payWallContentPart')
            || str_contains($html, 'pw2-login-hint')
            || str_contains($html, '"restricted":true');
    }

    /**
     * Puts the article image in front of the text; many readers do not show
     * a plain enclosure.
     */
    private function buildContent(string $image, string $body): string
    {
        if ($image === '') {
            return $body;
        }
        return '<p><img src="' . e($image) . '" alt=""></p>' . $body;
    }

    private function buildNotice(string $text): string
    {
        return '<p><strong>[FLZ-Bridge] ' . e($text) . '</strong></p>';
    }

    /**
     * Logs in at the SSO so that FLZ+ articles come with full text. Without
     * credentials nothing happens.
     *
     * The session is deliberately not kept in the cache: with the default
     * web server setup the cache folder is served as static files.
     */
    private function login(): void
    {
        $username = (string)$this->getOption('username');
        $password = (string)$this->getOption('password');
        if ($username === '' || $password === '') {
            $this->loginProblem = 'Keine FLZ+-Zugangsdaten in der Bridge-Konfiguration hinterlegt.';
            return;
        }

        if ($this->loadCacheValue('login_failed') !== null) {
            $this->loginProblem = sprintf(
                'Die letzte FLZ+-Anmeldung schlug fehl, weitere Versuche sind für bis zu %d Stunden ausgesetzt. Zugangsdaten und Abo prüfen.',
                self::LOGIN_LOCK_TTL / 3600
            );
            return;
        }

        try {
            $this->cookie = $this->postLogin($username, $password);
            $status = Json::decode(getContents(self::LOGON_STATUS_URI, [
                'Accept: application/json',
                'Cookie: ' . $this->cookie,
            ], $this->requestOptions()));
            if (!isset($status['code'])) {
                throw new \Exception('Unexpected answer from the logon status');
            }
        } catch (Exception $e) {
            // A network or server error is not a subscription problem: no
            // pause, the next refresh may try again.
            $this->logger->warning(sprintf('FLZ: login service failed: %s', $e->getMessage()));
            $this->cookie = '';
            $this->loginProblem = 'Der FLZ-Anmeldedienst (sso.flz.de) war nicht erreichbar oder lieferte einen Fehler.';
            return;
        }

        // The status endpoint answers 2000 when logged in and 2100 when not,
        // and is never cached, so it is the reliable check.
        if ((int)$status['code'] === 2000) {
            return;
        }

        $reason = !empty($status['message']) ? (string)$status['message'] : 'Sitzung nicht angemeldet';
        $this->logger->warning(sprintf('FLZ: login failed (%s), pausing for %d hours', $reason, self::LOGIN_LOCK_TTL / 3600));
        $this->saveCacheValue('login_failed', time(), self::LOGIN_LOCK_TTL);
        $this->loginProblem = sprintf('FLZ+-Anmeldung fehlgeschlagen (%s). Zugangsdaten und Abo prüfen.', $reason);
        $this->cookie = '';
    }

    /**
     * Submits the SSO login form and follows the redirects that establish
     * the session on www.flz.de.
     *
     * @return string Cookie header value
     */
    private function postLogin(string $username, string $password): string
    {
        // The form sets the cookies HASSOSESSID and __sso_csrf and posts to itself.
        $response = getContents(self::SSO_URI, [], [CURLOPT_FOLLOWLOCATION => false] + $this->requestOptions(), true);
        $jar = $this->mergeCookies($response);

        $response = getContents(self::SSO_URI, [
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: https://sso.flz.de',
            'Referer: ' . self::SSO_URI,
            'Cookie: ' . $jar,
        ], [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => $username,
                'password' => $password,
                'remember_me' => '1',
                // Without the named submit button the SSO reports a generic failure.
                'logon' => 'Anmeldung',
            ]),
            CURLOPT_FOLLOWLOCATION => false,
        ] + $this->requestOptions(), true);
        $jar = $this->mergeCookies($response, $jar);

        $url = self::SSO_URI;
        $location = $response->getHeader('location');
        for ($hops = 0; $hops < 5 && is_string($location) && $location !== ''; $hops++) {
            $url = urljoin($url, $location);
            if (!$this->isFlzHost($url)) {
                break;
            }
            $response = getContents($url, ['Cookie: ' . $jar], [CURLOPT_FOLLOWLOCATION => false] + $this->requestOptions(), true);
            $jar = $this->mergeCookies($response, $jar);
            $location = $response->getHeader('location');
        }
        return $jar;
    }

    /**
     * Merges the Set-Cookie headers of a response into a cookie jar string.
     */
    private function mergeCookies(Response $response, string $jar = ''): string
    {
        $cookies = [];
        foreach (explode('; ', $jar) as $pair) {
            if (str_contains($pair, '=')) {
                [$name, $value] = explode('=', $pair, 2);
                $cookies[$name] = $value;
            }
        }

        // Without the second argument getHeader() returns only the last of
        // several Set-Cookie headers.
        foreach ((array)$response->getHeader('set-cookie', true) as $line) {
            $pair = explode(';', (string)$line)[0];
            if (!str_contains($pair, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $pair, 2);
            if ($value !== '' && $value !== 'deleted') {
                $cookies[trim($name)] = $value;
            }
        }

        $parts = [];
        foreach ($cookies as $name => $value) {
            $parts[] = $name . '=' . $value;
        }
        return implode('; ', $parts);
    }

    /**
     * GET request that keeps the minimum distance to the previous request
     * and sends the session cookie only to flz.de.
     */
    private function fetch(string $url): string
    {
        $wait = self::REQUEST_INTERVAL_S - (microtime(true) - $this->lastRequest);
        if ($wait > 0) {
            usleep((int)($wait * 1000000));
        }
        $this->lastRequest = microtime(true);

        $headers = [
            'Accept-Language: de-DE,de;q=0.9',
            'Referer: ' . self::URI,
        ];
        if ($this->cookie !== '' && $this->isFlzHost($url)) {
            $headers[] = 'Cookie: ' . $this->cookie;
        }
        return getContents($url, $headers, $this->requestOptions());
    }

    /**
     * Caps the curl timeout so that a request ends before the deadline,
     * including the retry the HTTP client makes after a network error.
     */
    private function requestOptions(): array
    {
        $tries = 1 + max(0, (int)Configuration::getConfig('http', 'retries'));
        $timeout = (int)($this->secondsLeft() / $tries);
        $configured = (int)Configuration::getConfig('http', 'timeout');
        if ($configured > 0) {
            $timeout = min($timeout, $configured);
        }
        return [CURLOPT_TIMEOUT => max(1, $timeout)];
    }

    /**
     * The session cookie is only sent to flz.de, whatever a feed links to.
     * Redirects are followed by curl, which does not pass a Cookie header on
     * to another host (since curl 7.83.1).
     */
    private function isFlzHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return is_string($host) && ($host === 'flz.de' || str_ends_with($host, '.flz.de'));
    }

    /**
     * Runtime up to the deadline. max_execution_time can only shorten it
     * where PHP measures wall time, on Windows and on macOS with Apple
     * Silicon. On Linux it counts CPU time, so network waits do not count.
     */
    private function availableRuntime(): float
    {
        $runtime = (float)self::DEADLINE_S;
        $maxExecutionTime = (int)ini_get('max_execution_time');
        $wallClock = PHP_OS_FAMILY === 'Windows' || (PHP_OS_FAMILY === 'Darwin' && php_uname('m') === 'arm64');
        if ($maxExecutionTime > 0 && $wallClock) {
            $runtime = min($runtime, max(5.0, $maxExecutionTime - 2.0));
        }
        return $runtime;
    }

    /**
     * @param float $share Part of the runtime available to the caller
     */
    private function secondsLeft(float $share = 1.0): float
    {
        return $this->started + $this->runtime * $share - microtime(true);
    }

    private function hasTimeLeft(float $share = 1.0): bool
    {
        return $this->secondsLeft($share) >= self::MIN_REQUEST_S;
    }
}
