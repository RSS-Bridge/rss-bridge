<?php

class BandcampDailyBridge extends BridgeAbstract
{
    const NAME = 'Bandcamp Daily Bridge';
    const URI = 'https://daily.bandcamp.com';
    const DESCRIPTION = 'Returns newest articles';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'Latest articles' => [],
        'Best of' => [
            'best-content' => [
                'name' => 'content',
                'type' => 'list',
                'values' => [
                    'Best Ambient' => 'best-ambient',
                    'Best Beat Tapes' => 'best-beat-tapes',
                    'Best Dance 12\'s' => 'best-dance-12s',
                    'Best Contemporary Classical' => 'best-contemporary-classical',
                    'Best Electronic' => 'best-electronic',
                    'Best Experimental' => 'best-experimental',
                    'Best Hip-Hop' => 'best-hip-hop',
                    'Best Jazz' => 'best-jazz',
                    'Best Metal' => 'best-metal',
                    'Best Punk' => 'best-punk',
                    'Best Reissues' => 'best-reissues',
                    'Best Soul' => 'best-soul',
                ],
                'defaultValue' => 'best-ambient',
            ],
        ],
        'Genres' => [
            'genres-content' => [
                'name' => 'content',
                'type' => 'list',
                'values' => [
                    'Acoustic' => 'genres/acoustic',
                    'Alternative' => 'genres/alternative',
                    'Ambient' => 'genres/ambient',
                    'Blues' => 'genres/blues',
                    'Classical' => 'genres/classical',
                    'Comedy' => 'genres/comedy',
                    'Country' => 'genres/country',
                    'Devotional' => 'genres/devotional',
                    'Electronic' => 'genres/electronic',
                    'Experimental' => 'genres/experimental',
                    'Folk' => 'genres/folk',
                    'Funk' => 'genres/funk',
                    'Hip-Hop/Rap' => 'genres/hip-hop-rap',
                    'Jazz' => 'genres/jazz',
                    'Kids' => 'genres/kids',
                    'Latin' => 'genres/latin',
                    'Metal' => 'genres/metal',
                    'Pop' => 'genres/pop',
                    'Punk' => 'genres/punk',
                    'R&B/Soul' => 'genres/r-b-soul',
                    'Reggae' => 'genres/reggae',
                    'Rock' => 'genres/rock',
                    'Soundtrack' => 'genres/soundtrack',
                    'Spoken Word' => 'genres/spoken-word',
                    'World' => 'genres/world',
                ],
                'defaultValue' => 'genres/acoustic',
            ],
        ],
        'Franchises' => [
            'franchises-content' => [
                'name' => 'content',
                'type' => 'list',
                'values' => [
                    'Lists' => 'lists',
                    'Features' => 'features',
                    'Album of the Day' => 'album-of-the-day',
                    'Acid Test' => 'acid-test',
                    'Bandcamp Navigator' => 'bandcamp-navigator',
                    'Big Ups' => 'big-ups',
                    'Certified' => 'certified',
                    'Gallery' => 'gallery',
                    'Hidden Gems' => 'hidden-gems',
                    'High Scores' => 'high-scores',
                    'Label Profile' => 'label-profile',
                    'Lifetime Achievement' => 'lifetime-achievement',
                    'Scene Report' => 'scene-report',
                    'Seven Essential Releases' => 'seven-essential-releases',
                    'The Merch Table' => 'the-merch-table',
                ],
                'defaultValue' => 'lists',
            ],
        ]
    ];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Could not request: ' . $this->getURI());

        $html = defaultLinkTo($html, self::URI);

        $articles = $html->find('articles-list', 0);

        foreach ($articles->find('div.list-article') as $index => $article) {
            $item = [];

            $articlePath = $article->find('a.title', 0)->href;

            $articlePageHtml = getSimpleHTMLDOMCached($articlePath, 3600)
                or returnServerError('Could not request: ' . $articlePath);

            $item['uri'] = $articlePath;
            $item['title'] = $articlePageHtml->find('article-title', 0)->innertext;
            $item['author'] = $articlePageHtml->find('article-credits > a', 0)->innertext;
            $item['content'] = html_entity_decode($articlePageHtml->find('meta[name="description"]', 0)->content, ENT_QUOTES);
            $item['timestamp'] = $articlePageHtml->find('meta[property="article:published_time"]', 0)->content;
            $item['categories'][] = $articlePageHtml->find('meta[property="article:section"]', 0)->content;

            if ($articlePageHtml->find('meta[property="article:tag"]', 0)) {
                $item['categories'][] = $articlePageHtml->find('meta[property="article:tag"]', 0)->content;
            }

            $item['enclosures'][] = $articlePageHtml->find('meta[name="twitter:image"]', 0)->content;

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Latest articles':
                return self::URI . '/latest';
            case 'Best of':
            case 'Genres':
            case 'Franchises':
                // TODO Switch to array_key_first once php >= 7.3
                $contentKey = key(self::PARAMETERS[$this->queriedContext]);
                return self::URI . '/' . $this->getInput($contentKey);
            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Latest articles':
                return $this->queriedContext . ' - Bandcamp Daily';
            case 'Best of':
            case 'Genres':
            case 'Franchises':
                $contentKey = array_key_first(self::PARAMETERS[$this->queriedContext]);
                $contentValues = array_flip(self::PARAMETERS[$this->queriedContext][$contentKey]['values']);

                return $contentValues[$this->getInput($contentKey)] . ' - Bandcamp Daily';
            default:
                return parent::getName();
        }
    }
}
