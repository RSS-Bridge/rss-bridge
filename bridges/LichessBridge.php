<?php
class LichessBridge  extends FeedExpander {

    const MAINTAINER = 'AmauryCarrade';
    const NAME = 'Lichess Blog';
    const URI = 'http://fr.lichess.org/blog';
    const DESCRIPTION = 'Returns the 5 newest posts from the Lichess blog (full text)';

    public function collectData(){
        $this->collectExpandableDatas(self::URI . '.atom', 5);
    }

    protected function parseItem($newsItem){
        $item = parent::parseItem($newsItem);
        $item['content'] = $this->retrieve_lichess_post($item['uri']);
        return $item;
    }

    private function retrieve_lichess_post($blog_post_uri){
        $blog_post_html = getSimpleHTMLDOMCached($blog_post_uri);
        $blog_post_div  = $blog_post_html->find('#lichess_blog', 0);

        $post_chapo   = $blog_post_div->find('.shortlede', 0)->innertext;
        $post_content = $blog_post_div->find('.body', 0)->innertext;

        $content  = '<p><em>' . $post_chapo . '</em></p>';
        $content .= '<div>' . $post_content . '</div>';

        return $content;
    }
}
