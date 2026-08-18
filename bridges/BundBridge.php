<?php

declare(strict_types=1);

class BundBridge extends BridgeAbstract
{
    public const NAME = 'BUND';
    public const URI = 'https://www.bund.net';
    public const DESCRIPTION = 'Bund für Umwelt und Naturschutz Deutschland (BUND)';
    public const MAINTAINER = 'tillcash';
    public const CACHE_TIMEOUT = 3600;

    public const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Öko-Tipps' => '/bund-tipps/oekotipps',
                    'Pressemitteilungen' => '/service/presse/pressemitteilungen',
                    'Aktuelles' => '/themen/aktuelles',
                ],
            ],
        ],
    ];

    private const ITEM_LIMIT = 5;

    public function getIcon(): string
    {
        return self::URI . '/_assets/f79c34f38845eded732a54cf989b697f/Bund/Images/favicon.ico';
    }

    public function getName(): string
    {
        $category = $this->getKey('category');
        return self::NAME . ($category ? ': ' . $category : '');
    }

    public function collectData(): void
    {
        $headers = [
            'User-Agent: Mozilla/5.0 Version/17.0 Safari',
        ];

        $categoryPath = $this->getInput('category');
        $categoryUrl = self::URI . $categoryPath;
        $categoryHtml = getSimpleHTMLDOM($categoryUrl, $headers);

        $articles = $categoryHtml->find('article.m-content-dashboardbox');
        if (empty($articles)) {
            throwServerException('No articles found on the listing page.');
        }

        $count = 0;
        foreach ($articles as $article) {
            if ($count >= self::ITEM_LIMIT) {
                break;
            }

            $anchor = $article->find('a.m-content-dashboardbox--anchor', 0);
            if (!$anchor) {
                continue;
            }

            $url = urljoin(self::URI, $anchor->href);
            $articleHtml = getSimpleHTMLDOMCached($url, 86400, $headers);
            if (!$articleHtml) {
                continue;
            }

            $ogTitle = $articleHtml->find('meta[property="og:title"]', 0);
            $ogImage = $articleHtml->find('meta[property="og:image"]', 0);

            $title = $ogTitle ? $ogTitle->content : '';
            $image = $ogImage ? $ogImage->content : '';

            if (empty($title)) {
                $titleElem = $article->find('h3.m-content-dashboardbox--title', 0);
                $title = $titleElem ? trim($titleElem->plaintext) : '';
            }

            $captionElem = $article->find('p.rte-paragraph.rte-paragraph__caption', 0);
            $dateStr = $captionElem ? trim($captionElem->plaintext) : '';
            $timestamp = $this->parseGermanDate($dateStr);

            $mainContentElem = $articleHtml->find('.das-hier-ist-column-main', 0);
            $bodyContent = '';

            if ($mainContentElem) {
                $unwantedElements = [
                    '.c-donation-box',
                    '.rte-video-embed',
                    'p.rte-paragraph__clearfix',
                    'p span.rte-image',
                ];

                foreach ($mainContentElem->find(implode(', ', $unwantedElements)) as $node) {
                    $node->outertext = '';
                }

                $bodyContent = trim($mainContentElem->innertext);
            }

            $content = '';
            if (!empty($image)) {
                $content .= '<p><img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($title) . '" /></p>';
            }
            $content .= $bodyContent;

            $this->items[] = [
                'title' => $title,
                'uri' => $url,
                'uid' => $url,
                'timestamp' => $timestamp,
                'content' => $content,
            ];

            $count++;
        }
    }

    private function parseGermanDate(string $dateStr): int
    {
        if (empty($dateStr)) {
            return time();
        }

        $dateStr = trim(preg_replace('/\s*\|.*$/', '', $dateStr));

        $months = [
            'Januar' => '01', 'Jan' => '01',
            'Februar' => '02', 'Febr' => '02', 'Feb' => '02',
            'März' => '03', 'Maerz' => '03', 'Mrz' => '03', 'Mzg' => '03',
            'April' => '04', 'Apr' => '04',
            'Mai' => '05',
            'Juni' => '06', 'Jun' => '06',
            'Juli' => '07', 'Jul' => '07',
            'August' => '08', 'Aug' => '08',
            'September' => '09', 'Sept' => '09', 'Sep' => '09',
            'Oktober' => '10', 'Okt' => '10',
            'November' => '11', 'Nov' => '11',
            'Dezember' => '12', 'Dez' => '12',
        ];

        foreach ($months as $name => $num) {
            $dateStr = str_ireplace($name, $num, $dateStr);
        }

        foreach (['!d. m Y', '!j. m Y', '!d.m.Y', '!j.n.Y', '!d. m. Y'] as $format) {
            $dt = \DateTime::createFromFormat($format, $dateStr);
            if ($dt !== false) {
                return $dt->getTimestamp();
            }
        }

        $parsed = strtotime($dateStr);
        return $parsed !== false && $parsed > 0 ? $parsed : time();
    }
}
