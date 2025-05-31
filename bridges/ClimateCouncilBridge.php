<?php

class ClimateCouncilBridge extends BridgeAbstract
{
    const NAME = 'Climate Council Media Releases';
    const URI = 'https://www.climatecouncil.org.au/resource/media-releases/';
    const DESCRIPTION = 'Latest media releases from the Climate Council';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);
        if (!$html) {
            returnServerError('Could not load site HTML');
        }

        foreach ($html->find('article.post') as $article) {
            $a = $article->find('h3.post__title a', 0);
            if (!$a) {
                continue;
            }

            $title = trim($a->plaintext);
            $url = $a->href;
            if (strpos($url, 'http') !== 0) {
                $url = 'https://www.climatecouncil.org.au' . $url;
            }

            // Get thumbnail image from list page
            $img = $article->find('div.post__image img', 0);
            $thumbnailUrl = $img ? $img->src : '';

            // Get category tag(s) from list page
            $categorySpan = $article->find('span.post__meta__category', 0);
            $categories = [];
            if ($categorySpan) {
                $categories[] = trim($categorySpan->plaintext);
            }

            // Default values
            $content = '';
            $timestamp = time();
            $author = 'Climate Council';

            // Fetch and parse article page
            $articleHtml = getSimpleHTMLDOM($url);
            if ($articleHtml) {
                $contentDiv = $articleHtml->find('div.entry-content', 0);
                if ($contentDiv) {
                    $endsFound = false;
                    foreach ($contentDiv->find('p') as $p) {
                        if (trim($p->plaintext) === 'ENDS') {
                            $endsFound = true;
                            $p->outertext = ''; // Remove "ENDS" marker
                            continue;
                        }
                        if ($endsFound) {
                            $p->outertext = ''; // Remove everything after ENDS
                        }
                    }

                    // Prepend thumbnail if available
                    if ($thumbnailUrl) {
                        $thumbnailHtml = '<img src="' . $thumbnailUrl . '" style="max-width:100%; height:auto;"><br>';
                        $content = $thumbnailHtml . $contentDiv->innertext;
                    } else {
                        $content = $contentDiv->innertext;
                    }
                }

                $timeEl = $articleHtml->find('time', 0);
                if ($timeEl && isset($timeEl->datetime)) {
                    $timestamp = strtotime($timeEl->datetime);
                }

                $authorEl = $articleHtml->find('a[rel=author]', 0);
                if ($authorEl) {
                    $author = trim($authorEl->plaintext);
                }
            }

            $this->items[] = [
                'title' => $title,
                'uri' => $url,
                'author' => $author,
                'timestamp' => $timestamp,
                'content' => $content ?: 'Content not found.',
                'categories' => $categories,
            ];
        }
    }
}
