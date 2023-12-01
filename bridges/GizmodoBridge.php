<?php

class GizmodoBridge extends FeedExpander
{
    const MAINTAINER = 'polopollo';
    const NAME = 'Gizmodo';
    const URI = 'https://gizmodo.com';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the newest posts from Gizmodo.';

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);

        $html = defaultLinkTo($html, $this->getURI());
        $this->stripTags($html);
        $this->handleFigureTags($html);
        $this->handleIframeTags($html);

        // Get header image
        $image = $html->find('meta[property="og:image"]', 0)->content;

        $item['content'] = $html->find('div.js_post-content', 0)->innertext ?? '';

        // Get categories
        $categories = explode(',', $html->find('meta[name="keywords"]', 0)->content);
        $item['categories'] = array_map('trim', $categories);

        $item['enclosures'][] = $html->find('meta[property="og:image"]', 0)->content;

        return $item;
    }

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . '/rss', 20);
    }

    private function stripTags($html)
    {
        foreach ($html->find('aside') as $aside) {
            $aside->outertext = '';
        }

        foreach ($html->find('div.ad-unit') as $div) {
            $div->outertext = '';
        }

        foreach ($html->find('script') as $script) {
            $script->outertext = '';
        }
    }

    private function handleFigureTags($html)
    {
        foreach ($html->find('figure') as $index => $figure) {
            if (isset($figure->attr['data-id'])) {
                $id = $figure->attr['data-id'];
                $format = $figure->attr['data-format'];
            } else {
                $img = $figure->find('img', 0);
                $id = $img->attr['data-chomp-id'];
                $format = $img->attr['data-format'];
                $figure->find('div.img-permalink-sub-wrapper', 0)->style = '';
            }

            $imageUrl = 'https://i.kinja-img.com/gawker-media/image/upload/' . $id . '.' . $format;

            $figure->find('span', 0)->outertext = <<<EOD
<img src="{$imageUrl}">
EOD;
        }
    }

    private function handleIframeTags($html)
    {
        foreach ($html->find('iframe') as $iframe) {
            $iframe->src = urljoin($this->getURI(), $iframe->src);
        }
    }
}
