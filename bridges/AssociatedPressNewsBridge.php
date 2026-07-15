<?php

class AssociatedPressNewsBridge extends BridgeAbstract
{
    const NAME = 'Associated Press News';
    const URI = 'https://apnews.com/';
    const DESCRIPTION = 'Returns latest articles from categories';
    const MAINTAINER = 'anlar';
    const PARAMETERS = [
        'Standard Category' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All'              => '/',
                    'AP Fact Check'    => '/ap-fact-check',
                    'Business'         => '/business',
                    'Climate'          => '/climate-and-environment',
                    'Entertainment'    => '/entertainment',
                    'Health'           => '/health',
                    'Lifestyle'        => '/lifestyle',
                    'Oddities'         => '/oddities',
                    'Photography'      => '/photography',
                    'Politics'         => '/politics',
                    'Religion'         => '/religion',
                    'Science'          => '/science',
                    'Sports'           => '/sports',
                    'Technology'       => '/technology',
                    'U.S. News'        => '/us-news',
                    'World News'       => '/world-news',
                ],
                'defaultValue' => '/',
            ],
            'limit' => self::LIMIT + [
                'defaultValue' => 10,
            ],
        ],
        'Custom Category' => [
            'category' => [
                'name' => 'Path',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '/hub/animals',
            ],
            'limit' => self::LIMIT + [
                'defaultValue' => 10,
            ],
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://apnews.com/' => ['context' => 'Standard Category', 'category' => '/'],
        'https://apnews.com/health' => ['context' => 'Standard Category', 'category' => '/health'],
        'https://apnews.com/hub/animals' => ['context' => 'Custom Category', 'category' => '/hub/animals'],
    ];

    const GRAPHQL_ENDPOINT = 'https://apnews.com/graphql/delivery/ap/v1';
    const PERSISTED_QUERY_HASH = '3bc305abbf62e9e632403a74cc86dc1cba51156d2313f09b3779efec51fc3acb';

    public function detectParameters($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        $standardPaths = array_values(self::PARAMETERS['Standard Category']['category']['values']);
        if (in_array($path, $standardPaths, true)) {
            return ['context' => 'Standard Category', 'category' => $path];
        }

        if (str_starts_with($url, self::URI)) {
            return ['context' => 'Custom Category', 'category' => $path];
        }

        return null;
    }

    public function getURI()
    {
        $path = $this->getInput('category');
        if ($path && $path !== '/') {
            return self::URI . ltrim($path, '/');
        }
        return parent::getURI();
    }

    public function collectData()
    {
        $path = $this->getInput('category') ?: '/';

        $url = self::GRAPHQL_ENDPOINT . '?' . http_build_query([
            'operationName' => 'ContentPageQuery',
            'variables' => json_encode(['path' => $path], JSON_UNESCAPED_SLASHES),
            'extensions' => json_encode([
                'persistedQuery' => [
                    'version' => 1,
                    'sha256Hash' => self::PERSISTED_QUERY_HASH,
                ]
            ]),
        ]);

        $json = getContents($url);
        $data = json_decode($json, true);

        if (array_key_exists('Screen', $data['data'] ?? []) && $data['data']['Screen'] === null) {
            throw new \Exception('Category not found: ' . $path);
        }

        if (empty($data['data']['Screen'])) {
            throw new \Exception('Unexpected API response: Screen data missing');
        }

        $screen = $data['data']['Screen'];
        $isCustom = $this->queriedContext === 'Custom Category';
        $screenCategory = $screen['category'] ?? null;
        // All, photography and custom categories will contain multiple
        // categories in articles, so don't filter them
        $filterCategory = ($isCustom || $path === '/' || $path === '/photography') ? null : $screenCategory;
        $main = $screen['main'] ?? [];
        $seen = [];

        foreach ($main as $container) {
            if (($container['__typename'] ?? null) !== 'ColumnContainer') {
                continue;
            }
            foreach ($container['columns'] ?? [] as $column) {
                if (($column['__typename'] ?? null) !== 'PageListModule') {
                    continue;
                }
                foreach ($column['items'] ?? [] as $promo) {
                    if (($promo['__typename'] ?? null) !== 'PagePromo') {
                        continue;
                    }
                    if ($filterCategory && ($promo['category'] ?? null) !== $filterCategory) {
                        continue;
                    }

                    $id = $promo['id'] ?? null;
                    $url = $promo['url'] ?? null;

                    if (!$url || !$id || isset($seen[$id])) {
                        continue;
                    }
                    $seen[$id] = true;

                    $item = [];
                    $item['uid'] = $id;
                    $item['title'] = $promo['title'] ?? '';
                    $item['content'] = $promo['description'] ?? '';
                    $item['uri'] = $url;
                    $item['_imageUrl'] = $this->extractImageUrl($promo['media'] ?? []);

                    $stamp = $promo['publishDateStamp'] ?? null;
                    if ($stamp !== null) {
                        $item['timestamp'] = (int) ($stamp / 1000);
                    }

                    $categories = array_values(array_unique(array_filter([
                        $promo['category'] ?? null,
                        $isCustom ? $screenCategory : null,
                    ])));
                    if ($categories) {
                        $item['categories'] = $categories;
                    }

                    $this->items[] = $item;
                }
            }
        }

        usort($this->items, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $limit = (int) $this->getInput('limit');
        if ($limit > 0) {
            $this->items = array_slice($this->items, 0, $limit);
        }

        foreach ($this->items as &$item) {
            $this->collectPageData($item);
        }
    }

    private function collectPageData(array &$item): void
    {
        $imageUrl = $item['_imageUrl'];
        unset($item['_imageUrl']);

        $html = getSimpleHTMLDOM($item['uri']);

        $isVideo = str_contains(parse_url($item['uri'], PHP_URL_PATH), '/video/');
        if ($isVideo) {
            $ldScript = $html->find('script[type="application/ld+json"]', 0);
            $videoUrl = null;
            if ($ldScript) {
                $ld = json_decode($ldScript->innertext, true);
                $videoUrl = $ld['mainEntity']['contentUrl'] ?? null;
            }
            if ($videoUrl) {
                $descMeta = $html->find('meta[property="og:description"]', 0);
                $desc = $descMeta ? '<p>' . htmlspecialchars($descMeta->content, ENT_QUOTES) . '</p>' : '';
                $item['content'] = '<video controls src="' . $videoUrl . '"></video>' . $desc;
            }
        } else {
            $carouselHtml = $this->buildCarouselHtml($html);
            if ($carouselHtml) {
                $item['content'] = $carouselHtml;
            } elseif ($imageUrl) {
                $altMeta = $html->find('meta[property="og:image:alt"]', 0);
                $alt = $altMeta ? htmlspecialchars($altMeta->content, ENT_QUOTES) : '';
                $item['content'] = '<img src="' . $imageUrl . '" alt="' . $alt . '">';
            }

            $body = $html->find('div.RichTextStoryBody.RichTextBody', 0);
            if ($body) {
                foreach ($body->children() as $child) {
                    if ($child->tag === 'div' && ($child->class ?? '') !== 'Enhancement') {
                        $child->outertext = '';
                    }
                }
                $item['content'] = $item['content'] . $body->innertext;
            }
        }

        $authorsDiv = $html->find('div.Page-authors', 0);
        if ($authorsDiv) {
            $nodes = $authorsDiv->find('a, span.Link');
            $names = array_map(fn($n) => $n->plaintext, $nodes);
            if ($names) {
                $item['author'] = implode(', ', $names);
            }
        }
    }

    private function buildCarouselHtml($html): string
    {
        $carouselSlides = $html->find('div.Carousel-slides', 0);
        $slides = $carouselSlides ? $carouselSlides->find('div[class="CarouselSlide-media imageSlide"]') : [];

        $out = '';
        foreach ($slides as $i => $slide) {
            $img = $slide->find('picture img', 0); // used to get picture title
            $source = $slide->find('picture source', 0); // use to get picture URL

            $srcset = null;
            foreach ($source->attr as $attrName => $attrValue) {
                if (str_contains($attrName, 'srcset')) {
                    $srcset = $attrValue;
                    break;
                }
            }

            $srcsetEntries = explode(',', $srcset);
            $lastEntry = trim(end($srcsetEntries));
            $src = trim(explode(' ', $lastEntry)[0]);
            $caption = $img->alt ?? '';

            if ($src) {
                $out .= '<figure>';
                $out .= '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '">';
                if ($caption) {
                    $out .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES) . '</figcaption>';
                }
                $out .= '</figure>';
            }
        }
        return $out;
    }

    private function extractImageUrl(array $media): ?string
    {
        foreach ($media as $m) {
            if (($m['__typename'] ?? null) !== 'Image') {
                continue;
            }
            foreach ($m['image']['entries'] ?? [] as $entry) {
                if ($entry['key'] === 'src') {
                    return $entry['value'];
                }
            }
        }
        return null;
    }
}
