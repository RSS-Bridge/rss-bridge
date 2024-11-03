<?php

class TagesspiegelBridge extends FeedExpander
{
    const MAINTAINER = 'AlexanderS';
    const NAME = 'Tagesspiegel Bridge';
    const URI = 'https://www.tagesspiegel.de/';
    const CACHE_TIMEOUT = 3600; // 60min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Startseite'
                => 'https://tagesspiegel.de/contentexport/feed',
                'Plus'
                => 'https://tagesspiegel.de/contentexport/feed/plus/',
                'Politik'
                => 'https://tagesspiegel.de/contentexport/feed/politik/',
                'Internationales'
                => 'https://tagesspiegel.de/contentexport/feed/internationales/',
                'Berlin'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/',
                'Berlin - Bezirke'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/bezirke/',
                'Berlin - Berliner Wirtschaft'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/berliner-wirtschaft/',
                'Berlin - Berliner Sport'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/berliner_sport/',
                'Berlin - Polizei & Justiz'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/polizei-justiz/',
                'Berlin - Stadtleben'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/stadtleben/',
                'Berlin - Schule'
                => 'https://tagesspiegel.de/contentexport/feed/berlin/schule/',
                'Gesellschaft'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/',
                'Gesellschaft - Liebe & Partnerschaft'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/liebe-partnerschaft/',
                'Gesellschaft - Queer'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/queerspiegel/',
                'Gesellschaft - Panorama'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/panorama/',
                'Gesellschaft - Medien'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/medien/',
                'Gesellschaft - Geschichte'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/geschichte/',
                'Gesellschaft - Reise'
                => 'https://tagesspiegel.de/contentexport/feed/gesellschaft/reise/',
                'Wirtschaft'
                => 'https://tagesspiegel.de/contentexport/feed/wirtschaft/',
                'Wirtschaft - Immobilien'
                => 'https://tagesspiegel.de/contentexport/feed/wirtschaft/immobilien/',
                'Wirtschaft - Jobs & Karriere'
                => 'https://tagesspiegel.de/contentexport/feed/wirtschaft/karriere/',
                'Wirtschaft - Finanzen'
                => 'https://tagesspiegel.de/contentexport/feed/wirtschaft/finanzen/',
                'Wirtschaft - Mobilität'
                => 'https://tagesspiegel.de/contentexport/feed/wirtschaft/mobilitaet/',
                'Kultur'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/',
                'Kultur - Literatur'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/literatur/',
                'Kultur - Comics'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/comics/',
                'Kultur - Kino'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/kino/',
                'Kultur - Pop'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/pop/',
                'Kultur - Ausstellungen'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/ausstellungen/',
                'Kultur - Bühne'
                => 'https://tagesspiegel.de/contentexport/feed/kultur/buehne/',
                'Wissen'
                => 'https://tagesspiegel.de/contentexport/feed/wissen/',
                'Gesundheit'
                => 'https://tagesspiegel.de/contentexport/feed/gesundheit/',
                'Sport'
                => 'https://tagesspiegel.de/contentexport/feed/sport/',
                'Meinung'
                => 'https://tagesspiegel.de/contentexport/feed/meinung/',
                'Meinung - Kolumnen'
                => 'https://tagesspiegel.de/contentexport/feed/meinung/kolumnen/',
                'Meinung - Lesermeinung'
                => 'https://tagesspiegel.de/contentexport/feed/meinung/lesermeinung/',
                'Potsdam'
                => 'https://tagesspiegel.de/contentexport/feed/potsdam/',
                'Potsdam - Landeshauptstadt'
                => 'https://tagesspiegel.de/contentexport/feed/potsdam/landeshauptstadt/',
                'Potsdam - Potsdam-Mittelmark'
                => 'https://tagesspiegel.de/contentexport/feed/potsdam/potsdam-mittelmark/',
                'Potsdam - Brandenburg'
                => 'https://tagesspiegel.de/contentexport/feed/potsdam/brandenburg/',
                'Potsdam - Kultur'
                => 'https://tagesspiegel.de/contentexport/feed/potsdam/potsdam-kultur/',
                'Podcasts'
                => 'https://tagesspiegel.de/contentexport/feed/podcasts/',
            ]
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 5
        ]
    ]];

    public function collectData()
    {
        $url = $this->getInput('category');
        $limit = $this->getInput('limit') ?: 5;

        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem(array $item)
    {
        $item['enclosures'] = [];

        $article = getSimpleHTMLDOM($item['uri']);
        $item = $this->parseArticle($item, $article);

        return $item;
    }

    private function parseArticle($item, $article)
    {
        $item['categories'] = [];

        // Add tag for articles only available with "Tagesspiegel Plus"
        $plusicon = $article->find('span[data-ob="plus"]', 0);
        if ($plusicon) {
            $item['categories'][] = 'Tagesspiegel Plus';
        }

        // Add section from breadcrumbs as tags
        $breadcrumbs = $article->find('ol[property="breadcrumb"]', 0);
        $names = $breadcrumbs->find('span[property="name"]');
        $names = array_slice($names, 1, -1);
        foreach ($names as $name) {
            $item['categories'][] = trim($name->plaintext);
        }

        // Get categories from article
        $home_link = $article->find('a[data-gtm-class="article-home-link"]', 0);
        if ($home_link) {
            $tag_container = $home_link->parent->nextSibling();
            if ($tag_container) {
                $tags = $tag_container->find('li');

                if ($tags) {
                    foreach ($tags as $tag) {
                        $item['categories'][] = trim($tag->plaintext);
                    }
                }
            }
        }

        $article = $article->find('article', 0);

        // Remove known bad elements
        foreach (
            $article->find(
                'script, aside, nav, dl.debug-piano, .link--external svg, time, a[data-gtm-class="article-home-link"]'
            ) as $bad
        ) {
            $bad->remove();
        }

        // Remove references to external content (requires javascript for consent)
        foreach ($article->find('p') as $par) {
            if ($par->plaintext == 'Empfohlener redaktioneller Inhalt') {
                $par->parent->parent->parent->parent->remove();
            }
        }

        // Reload html, as remove() is buggy
        $article = str_get_html($article->outertext);


        // Clean article content
        $elements = $article->find('h3, p, figure, blockquote');
        foreach ($elements as $i => $element) {
            foreach ($element->find('img, picture source') as $img) {
                // Add URI to src
                if ($img->hasAttribute('src')) {
                    if (str_starts_with($img->attr['src'], '/')) {
                        $img->attr['src'] = urljoin(self::URI, $img->attr['src']);
                    }
                }

                // Add URI to srcset
                if ($img->hasAttribute('srcset')) {
                    $srcsets = explode(',', $img->attr['srcset']);
                    foreach ($srcsets as &$srcset) {
                        $parts = explode(' ', trim($srcset));
                        if (count($parts) > 0) {
                            if (str_starts_with($parts[0], '/')) {
                                $parts[0] = urljoin(self::URI, $parts[0]);
                            }
                        }
                        $srcset = implode(' ', $parts);
                    }
                    $img->attr['srcset'] = implode(', ', $srcsets);
                }
            }

            // Remove paragraphs that are already included in other elements
            if ($element->tag == 'p') {
                if ($element->parent->tag == 'blockquote' || $element->parent->tag == 'figure') {
                    unset($elements[$i]);
                }
            }
        }
        $item['content'] = implode('', $elements);

        return $item;
    }
}
