<?php

class TheAustraliaInstituteBridge extends BridgeAbstract
{
    const NAME = 'The Australia Institute';
    const URI = 'https://australiainstitute.org.au/news/category/articles/';
    const DESCRIPTION = 'Media Releases from The Australia Institute';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request ' . self::URI);

        foreach ($html->find('article.Item') as $article) {
            $item = [];

            // URI
            $link = $article->find('a[rel=bookmark]', 0)->href;
            $item['uri'] = $link;

            // Title
            $titleElement = $article->find('h1.title a', 0);
            $item['title'] = $titleElement ? $titleElement->plaintext : 'No title';

            // Timestamp
            $timeElement = $article->find('time', 0);
            $item['timestamp'] = $timeElement ? strtotime($timeElement->datetime) : time();

            // Thumbnail with responsive style
            $thumbnail = '';
            $img = $article->find('div.Item_thumb img', 0);
            if ($img) {
                $thumbnail = '<img src="' . htmlspecialchars($img->src) . '" alt="" style="max-width:100%; height:auto;" />';
            }

            // Intro paragraph
            $intro = $article->find('div.Item_intro p', 0);
            $introText = $intro ? $intro->innertext : '';

            // Tags / Categories
            $tags = [];
            foreach ($article->find('footer.Item_tags li a') as $tagEl) {
                $tags[] = html_entity_decode($tagEl->plaintext, ENT_QUOTES | ENT_HTML5);
            }
            $item['categories'] = $tags;

            // Fetch full article content
            $articleHtml = getSimpleHTMLDOM($link);
            $fullContent = '';
            if ($articleHtml) {
                // Remove <div class="EntryMain_intro intro intro-large">
                foreach ($articleHtml->find('div.EntryMain_intro') as $introDiv) {
                    $introDiv->outertext = '';
                }

                // Get cleaned content
                $contentSection = $articleHtml->find('section.-typo', 0);
                if ($contentSection) {
                    $fullContent = $contentSection->innertext;
                }
            }

            // Final content block
            $item['content'] = $thumbnail . '<br/>' . $introText . '<hr/>' . $fullContent;

            // Author (optional for future)
            $item['author'] = '';

            $this->items[] = $item;
        }
    }
}
