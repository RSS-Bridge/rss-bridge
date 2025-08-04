<?php

class TheDailyAusBridge extends BridgeAbstract
{
    const NAME = 'The Daily Aus';
    const URI = 'https://thedailyaus.com.au/';
    const DESCRIPTION = 'Australia\'s leading social-first news service';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'category' => [
                'type' => 'list',
                'name' => 'Category',
                'title' => 'Choose category',
                'values' => [
                    'Latest' => 'category/latest',
                    'Politics' => 'category/politics',
                    'World' => 'category/world',
                    'Economics' => 'category/economics',
                    'Science' => 'category/science',
                    'Crime' => 'category/crime',
                    'Health' => 'category/health',
                    'Sport' => 'category/sport',
                    'Culture' => 'category/culture',
                ],
            ],
        ],
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . $this->getInput('category')) or returnServerError('Could not load page');
        $jsonScript = $html->find('script#__NEXT_DATA__', 0);

        if (!$jsonScript) {
            returnServerError('Could not find JSON data');
        }

        $jsonData = json_decode($jsonScript->innertext, true);
        $articles = $jsonData['props']['pageProps']['initialContents'] ?? [];

        if (empty($articles)) {
            returnServerError('Article data not found');
        }

        foreach ($articles as $article) {
            if (($article['_type'] ?? '') !== 'article') {
                continue;
            }

            $title = $article['displayTitle'] ?? 'No title';
            $slugLocation = trim($article['primaryCategory']['slug']['current'] ?? '', '/');
            $slug = trim($article['slug'] ?? '', '/');
            $uri = self::URI . $slugLocation . '/' . $slug;
            $timestamp = strtotime($article['publishDate'] ?? 'now');
            $image = $article['thumbnailUrl'] ?? '';
            $enclosures = $image ? [$image] : [];
            $content = '';
            $author = '';
            $categories = [];
            $link = '';

            try {
                $articleHtml = @getSimpleHTMLDOM($uri);
                $link = $uri;

                if (!$articleHtml) {
                    $uriWithDate = $uri . '-' . date('d-m-Y', $timestamp);
                    $articleHtml = getSimpleHTMLDOM($uriWithDate) or returnServerError('Could not load article page');
                    $link = $uriWithDate;
                }

                $articleScript = $articleHtml->find('script#__NEXT_DATA__', 0);
                if (!$articleScript) {
                    returnServerError('Could not find article JSON data');
                }

                $articleJson = json_decode($articleScript->innertext, true);
                $articleData = $articleJson['props']['pageProps']['article'] ?? [];

                // Author
                foreach ($articleData['authors'] ?? [] as $articleAuthor) {
                    $author = $articleAuthor['name'] ?? '';
                    break;
                }

                // Categories
                foreach ($articleData['topics'] ?? [] as $topic) {
                    $categories[] = ucwords(strtolower($topic['name'] ?? ''));
                }

                // Content blocks
                $blocks = $articleData['content'] ?? [];

                // Image prep
                $baseCdnUrl = '';
                if ($image) {
                    $content .= '<img src="' . htmlspecialchars($image) . '" /><br>';
                    if (preg_match('#^(https://cdn\.sanity\.io/images/[^/]+/production/)#', $image, $matches)) {
                        $baseCdnUrl = $matches[1];
                    }
                }

                foreach ($blocks as $block) {
                    if ($block['_type'] === 'block') {
                        $style = $block['style'] ?? 'normal';
                        $text = '';

                        foreach ($block['children'] as $child) {
                            $spanText = htmlspecialchars($child['text'] ?? '');
                            $spanMarks = $child['marks'] ?? [];

                            foreach ($spanMarks as $markKey) {
                                foreach ($block['markDefs'] ?? [] as $def) {
                                    if ($def['_key'] === $markKey && $def['_type'] === 'link') {
                                        $href = htmlspecialchars($def['href'] ?? '#');
                                        $spanText = '<a href="' . $href . '">' . $spanText . '</a>';
                                    }
                                }
                            }

                            $text .= $spanText;
                        }

                        switch ($style) {
                            case 'h2':
                                $content .= '<h2>' . $text . '</h2>';
                                break;
                            case 'h3':
                                $content .= '<h3>' . $text . '</h3>';
                                break;
                            default:
                                $content .= '<p>' . $text . '</p>';
                                break;
                        }
                    } elseif (
                        $block['_type'] === 'image'
                        && isset($block['asset']['_ref'])
                        && $baseCdnUrl
                    ) {
                        $ref = $block['asset']['_ref'];
                        if (preg_match('/^image-([a-f0-9]+-\d+x\d+)-(jpg|jpeg|png|webp)$/', $ref, $matches)) {
                            $imageId = $matches[1];
                            $extension = $matches[2];
                            $imageUrl = $baseCdnUrl . $imageId . '.' . $extension;
                            $enclosures[] = $imageUrl;
                            $content .= '<p><img src="' . htmlspecialchars($imageUrl) . '" /></p>';
                        }
                    }
                }

                $this->items[] = [
                    'title' => $title,
                    'uri' => $link,
                    'author' => $author,
                    'timestamp' => $timestamp,
                    'content' => $content ?: 'Content not found.',
                    'categories' => $categories,
                    'enclosures' => $enclosures,
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
