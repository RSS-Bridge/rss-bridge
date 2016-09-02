<?php

class LichessBridge  extends HttpCachingBridgeAbstract
{
    const MAINTAINER = 'AmauryCarrade';
    const NAME = 'Lichess Blog';
    const URI = 'http://fr.lichess.org/blog';
    const DESCRIPTION = 'Returns the 5 newest posts from the Lichess blog (full text)';

    public function collectData()
    {
        $xml_feed = $this->getSimpleHTMLDOM(self::URI.'.atom')
            or $this->returnServerError('Could not retrieve Lichess blog feed.');

        $posts_loaded = 0;
        foreach($xml_feed->find('entry') as $entry)
        {
            if ($posts_loaded < 5)
            {
                $item = array();

                $item['title']     = html_entity_decode($entry->find('title', 0)->innertext);
                $item['author']    = $entry->find('author', 0)->find('name', 0)->innertext;
                $item['uri']       = $entry->find('id', 0)->plaintext;
                $item['timestamp'] = strtotime($entry->find('published', 0)->plaintext);

                $item['content'] = $this->retrieve_lichess_post($item['uri']);

                $this->items[] = $item;
                $posts_loaded++;
            }
        }
    }

    private function retrieve_lichess_post($blog_post_uri)
    {
        if($this->get_cached_time($blog_post_uri) <= strtotime('-24 hours'))
            $this->remove_from_cache($blog_post_uriuri);

        $blog_post_html = $this->get_cached($blog_post_uri);
        $blog_post_div  = $blog_post_html->find('#lichess_blog', 0);

        $post_chapo   = $blog_post_div->find('.shortlede', 0)->innertext;
        $post_content = $blog_post_div->find('.body', 0)->innertext;

        $content  = '<p><em>' . $post_chapo . '</em></p>';
        $content .= '<div>' . $post_content . '</div>';

        return $content;
    }
}
