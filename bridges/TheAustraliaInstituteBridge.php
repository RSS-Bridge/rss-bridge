<?php

class TheAustraliaInstituteBridge extends BridgeAbstract
{
    const NAME = 'The Australia Institute';
    const URI = 'https://australiainstitute.org.au/news/category/articles/';
    const DESCRIPTION = 'Media Releases from The Australia Institute';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request ' . self::URI);

        foreach ($html->find('article.Item') as $article) {
            $item = [];

            $linkElement = $article->find('a[rel=bookmark]', 0);
            if (!$linkElement) {
                continue;
            }
            $item['uri'] = $linkElement->href;

            $titleElement = $article->find('h1.title a', 0);
            $item['title'] = $titleElement ? $titleElement->plaintext : 'No title';

            $timeElement = $article->find('time', 0);
            $item['timestamp'] = $timeElement ? strtotime($timeElement->datetime) : time();

            $img = $article->find('div.Item_thumb img', 0);
            $thumbnail = $img
                ? '<img src="' . htmlspecialchars($img->src) . '" alt="" style="max-width:100%; height:auto;" />'
                : '';

            $intro = $article->find('div.Item_intro p', 0);
            $introText = $intro ? $intro->innertext : '';

            $tags = [];
            foreach ($article->find('footer.Item_tags li a') as $tagEl) {
                $tags[] = html_entity_decode($tagEl->plaintext, ENT_QUOTES | ENT_HTML5);
            }
            $item['categories'] = $tags;

            $articleHtml = @getSimpleHTMLDOM($item['uri']);
            $fullContent = '';
            
            if ($articleHtml) {
                foreach ($articleHtml->find('div.EntryMain_intro') as $introDiv) {
                    $introDiv->outertext = '';
                }

                $contentSection = $articleHtml->find('section.-typo', 0);
                if ($contentSection) {
                    foreach ($contentSection->find('iframe') as $iframe) {
                        $src = $iframe->src ?? '';
                        $title = $iframe->title ?? 'Video';

                        if (strpos($src, 'youtube.com/embed/') !== false) {
                            $linkHtml = '<p><a href="' . htmlspecialchars($src) . '">Video: ' . htmlspecialchars($title) . '</a></p>';
                            $iframe->outertext = $linkHtml;
                        }
                    }

                    $fullContent = $contentSection->innertext;
                }
            }

            $item['content'] = $thumbnail . '<br/>' . $introText . '<hr/>' . $fullContent;
            $item['author'] = ''; // Optional future support

            $this->items[] = $item;
        }
    }
}
