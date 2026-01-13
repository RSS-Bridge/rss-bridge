<?php

class WWFAustraliaBridge extends BridgeAbstract
{
    const NAME = 'WWF Australia';
    const URI = 'https://wwf.org.au/';
    const DESCRIPTION = 'Latest WWF Australia news or blogs with full article content.';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'type' => [
                'name' => 'Content Type',
                'type' => 'list',
                'values' => [
                    'News' => 'news',
                    'Blogs' => 'blogs',
                ],
                'defaultValue' => 'news',
            ],
        ],
    ];

    public function collectData()
    {
        $type = $this->getInput('type');
        $mainPage = getSimpleHTMLDOM(self::URI . $type . '/');
        $buildId = null;

        foreach ($mainPage->find('script#__NEXT_DATA__') as $scriptTag) {
            $json = json_decode($scriptTag->innertext, true);
            if (isset($json['buildId'])) {
                $buildId = $json['buildId'];
                break;
            }
        }

        if (!$buildId) {
            returnServerError('Unable to extract Next.js buildId from main page');
        }

        $apiUrl = 'https://291t4y9i4t-dsn.algolia.net/1/indexes/wwf_website_prod_date_sorted/query';
        $headers = [
            'x-algolia-api-key: dd06aa34e50cc3f27dbd8fda34e27b88',
            'x-algolia-application-id: 291T4Y9I4T',
            'content-type: application/x-www-form-urlencoded',
        ];

        $recordType = $type === 'blogs' ? 'pageBlog' : 'pageNews';

        $postData = json_encode([
            'query' => '',
            'hitsPerPage' => 10,
            'filters' => "recordType:'$recordType'",
            'attributesToHighlight' => [],
            'attributesToSnippet' => [],
            'analyticsTags' => [],
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $postData,
            ],
        ]);

        $response = file_get_contents($apiUrl, false, $context);

        if ($response === false) {
            returnServerError('Failed to fetch data from WWF API');
        }

        $data = json_decode($response, true);

        foreach ($data['hits'] as $hit) {
            $item = [
                'uri' => $hit['url'],
                'title' => $hit['title'],
                'timestamp' => strtotime($hit['publishedDate']),
                'categories' => array_map(function ($tag) {
                    $raw = is_array($tag) ? ($tag['key'] ?? '') : (string) $tag;
                    return ucwords(str_replace('-', ' ', $raw));
                }, $hit['tags'] ?? []),
            ];

            $slug = basename($hit['url']);

            $jsonUrl = $type === 'blogs'
                ? "https://wwf.org.au/_next/data/$buildId/blogs/$slug.json"
                : "https://wwf.org.au/_next/data/$buildId/news/{$hit['publishedYear']}/$slug.json";

            $jsonArticle = json_decode(getContents($jsonUrl), true);
            $articleItem = $jsonArticle['pageProps']['pagePayload']['page']['items'][0] ?? null;

            $linkedEntries = [];

            foreach ($articleItem['bodyContent']['links']['entries']['block'] ?? [] as $entry) {
                $linkedEntries[$entry['sys']['id']] = $entry;
            }

            foreach ($articleItem['bodyContent']['links']['entries']['hyperlink'] ?? [] as $entry) {
                $linkedEntries[$entry['sys']['id']] = $entry;
            }

            $fullContent = null;

            if ($articleItem && isset($articleItem['bodyContent']['json'])) {
                $fullContent = $this->renderRichText($articleItem['bodyContent']['json'], $linkedEntries);
            }

            $image = '';

            if (!empty($hit['imageUrl'])) {
                $image = '<img src="' . htmlspecialchars($hit['imageUrl']) . '" alt="" /><br>';
            }

            if (!empty($articleItem['hero']['imageSource'][0]['secure_url'])) {
                $imageUrl = $articleItem['hero']['imageSource'][0]['secure_url'];
                $altText = $articleItem['hero']['imageSource'][0]['context']['custom']['alt'] ?? '';
                $image = '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($altText) . '" /><br>';
            }

            $item['content'] = $image . ($fullContent ?: $hit['content']);
            $this->items[] = $item;
        }
    }

    private function renderRichText($json, $linkedEntries = [])
    {
        $html = '';

        foreach ($json['content'] as $node) {
            switch ($node['nodeType']) {
                case 'paragraph':
                case 'heading-2':
                case 'heading-3':
                    $tag = $node['nodeType'] === 'paragraph' ? 'p' : ($node['nodeType'] === 'heading-2' ? 'h2' : 'h3');

                    $segment = '';

                    foreach ($node['content'] as $inline) {
                        $segment .= $this->renderInlineNode($inline, $linkedEntries);
                    }

                    $html .= "<$tag>$segment</$tag>";
                    break;

                case 'embedded-entry-block':
                    $entryId = $node['data']['target']['sys']['id'] ?? '';
                    if (isset($linkedEntries[$entryId])) {
                        $block = $linkedEntries[$entryId];

                        if ($block['__typename'] === 'ImageBlock') {
                            foreach ($block['imagesCollection']['items'] as $imageItem) {
                                $image = $imageItem['imageSource'][0] ?? null;
                                if ($image) {
                                    $html .= $this->renderImageHtml($image);
                                }
                            }
                        } elseif ($block['__typename'] === 'MediaImage') {
                            $image = $block['imageSource'][0] ?? null;
                            if ($image) {
                                $html .= $this->renderImageHtml($image);
                            }
                        }
                    }
                    break;
            }
        }

        return $html;
    }

    private function renderInlineNode($inline, $linkedEntries)
    {
        if ($inline['nodeType'] === 'text') {
            $text = htmlspecialchars($inline['value'] ?? '');
            foreach ($inline['marks'] ?? [] as $mark) {
                if ($mark['type'] === 'bold') {
                    $text = "<strong>$text</strong>";
                } elseif ($mark['type'] === 'italic') {
                    $text = "<em>$text</em>";
                }
            }
            return $text;
        }

        if ($inline['nodeType'] === 'hyperlink') {
            $url = htmlspecialchars($inline['data']['uri'] ?? '');
            $linkText = '';
            foreach ($inline['content'] as $linkNode) {
                $linkText .= $this->renderInlineNode($linkNode, $linkedEntries);
            }
            return "<a href=\"$url\">$linkText</a>";
        }

        if ($inline['nodeType'] === 'entry-hyperlink') {
            $entryId = $inline['data']['target']['sys']['id'] ?? '';
            $linkedEntry = $linkedEntries[$entryId] ?? null;
            $linkText = '';
            foreach ($inline['content'] as $linkNode) {
                $linkText .= $this->renderInlineNode($linkNode, $linkedEntries);
            }

            if ($linkedEntry && isset($linkedEntry['slug'])) {
                $href = self::URI . 'blogs/' . $linkedEntry['slug'];
                return "<a href=\"$href\">$linkText</a>";
            }

            return $linkText;
        }

        return '';
    }

    private function renderImageHtml($image)
    {
        $url = htmlspecialchars($image['secure_url'] ?? '');
        $alt = htmlspecialchars($image['context']['custom']['alt'] ?? '');
        $credit = htmlspecialchars($image['context']['custom']['credit'] ?? '');
        $caption = htmlspecialchars($image['context']['custom']['caption'] ?? '');

        $html = '<div style="margin: 1em 0;">';
        $html .= "<img src=\"$url\" alt=\"$alt\" style=\"max-width:100%;\" />";
        if ($caption || $credit) {
            $html .= '<p style="font-size: small; color: #555;">';
            if ($caption) {
                $html .= "<em>$caption</em><br>";
            }
            if ($credit) {
                $html .= "Credit: $credit";
            }
            $html .= '</p>';
        }
        $html .= '</div>';

        return $html;
    }
}
