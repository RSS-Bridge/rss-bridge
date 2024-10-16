<?php

class ArsTechnicaBridge extends FeedExpander
{
    const MAINTAINER = 'phantop';
    const NAME = 'Ars Technica';
    const URI = 'https://arstechnica.com/';
    const DESCRIPTION = 'Returns the latest articles from Ars Technica';
    const PARAMETERS = [[
            'section' => [
                'name' => 'Site section',
                'type' => 'list',
                'defaultValue' => 'index',
                'values' => [
                    'All' => 'index',
                    'Apple' => 'apple',
                    'Board Games' => 'cardboard',
                    'Cars' => 'cars',
                    'Features' => 'features',
                    'Gaming' => 'gaming',
                    'Information Technology' => 'technology-lab',
                    'Science' => 'science',
                    'Staff Blogs' => 'staff-blogs',
                    'Tech Policy' => 'tech-policy',
                    'Tech' => 'gadgets',
                    ]
            ]
    ]];

    public function collectData()
    {
        $url = 'https://feeds.arstechnica.com/arstechnica/' . $this->getInput('section');
        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem(array $item)
    {
        $item_html = getSimpleHTMLDOMCached($item['uri']);
        $item_html = defaultLinkTo($item_html, self::URI);

        $content = '';
        $header = $item_html->find('article header', 0);
        $leading = $header->find('p[class*=leading]', 0);
        if ($leading != null) {
            $content .= '<p>' . $leading->innertext . '</p>';
        }
        $intro_image = $header->find('img.intro-image', 0);
        if ($intro_image != null) {
            $content .= '<figure>' . $intro_image;

            $image_caption = $header->find('.caption .caption-content', 0);
            if ($image_caption != null) {
                $content .= '<figcaption>' . $image_caption->innertext . '</figcaption>';
            }
            $content .= '</figure>';
        }

        foreach ($item_html->find('.post-content') as $content_tag) {
            $content .= $content_tag->innertext;
        }

        $item['content'] = str_get_html($content);

        $parsely = $item_html->find('[name="parsely-page"]', 0);
        $parsely_json = json_decode(html_entity_decode($parsely->content), true);
        $item['categories'] = $parsely_json['tags'];

        // Some lightboxes are nested in figures. I'd guess that's a
        // bug in the website
        foreach ($item['content']->find('figure div div.ars-lightbox') as $weird_lightbox) {
            $weird_lightbox->parent->parent->outertext = $weird_lightbox;
        }

        // It's easier to reconstruct the whole thing than remove
        // duplicate reactive tags
        foreach ($item['content']->find('.ars-lightbox') as $lightbox) {
            $lightbox_content = '';
            foreach ($lightbox->find('.ars-lightbox-item') as $lightbox_item) {
                $img = $lightbox_item->find('img', 0);
                if ($img != null) {
                    $lightbox_content .= '<figure>' . $img;
                    $caption = $lightbox_item->find('div.pswp-caption-content', 0);
                    if ($caption != null) {
                        $credit = $lightbox_item->find('div.ars-gallery-caption-credit', 0);
                        if ($credit != null) {
                            $credit->innertext = 'Credit: ' . $credit->innertext;
                        }
                        $lightbox_content .= '<figcaption>' . $caption->innertext . '</figcaption>';
                    }
                    $lightbox_content .= '</figure>';
                }
            }
            $lightbox->innertext = $lightbox_content;
        }

        // remove various ars advertising
        foreach ($item['content']->find('.ars-interlude-container') as $ad) {
            $ad->remove();
        }
        foreach ($item['content']->find('.toc-container') as $toc) {
            $toc->remove();
        }

        // Mostly YouTube videos
        $iframes = $item['content']->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }
        // This fixed padding around the former iframes and actual inline videos
        foreach ($item['content']->find('div[style*=aspect-ratio]') as $styled) {
            $styled->removeAttribute('style');
        }

        $item['content'] = backgroundToImg($item['content']);
        $item['uid'] = strval($parsely_json['post_id']);
        return $item;
    }
}
