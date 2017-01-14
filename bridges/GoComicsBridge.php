<?php
class GoComicsBridge extends BridgeAbstract {

    const MAINTAINER    = 'sky';
    const NAME          = 'GoComics Unofficial RSS';
    const URI           = 'http://www.gocomics.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION   = 'The Unofficial GoComics RSS';
    const PARAMETERS    = array( array(
        'comicname' => array(
            'name'     => 'comicname',
            'type'     => 'text',
            'required' => true
        )
    ));

    public function collectData() {
        $html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request GoComics: '.$this->getURI());

        foreach ($html->find('div.item-comic-container') as $element) {

            $img   = $element->find('img', 0);
            $link  = $element->find('a.item-comic-link', 0);
            $comic = $img->src;
            $title = $link->title;
            $url   = $html->find('input.js-copy-link', 0)->value;
            $date  = substr($title, -10);
            if (empty($title))
                $title = 'GoComics '.$this->getInput('comicname').' on '.$date;
            $date = strtotime($date);

            $item              = array();
            $item['id']        = $url;
            $item['uri']       = $url;
            $item['title']     = $title;
            $item['author']    = preg_replace('/by /', '', $element->find('a.link-blended small', 0)->plaintext);
            $item['timestamp'] = $date;
            $item['content']   = '<img src="'.$comic.'" alt="'.$title.'" />';
            $this->items[]     = $item;
        }
    }

    public function getURI() {
        return self::URI.urlencode($this->getInput('comicname'));
    }

    public function getName() {
        return $this->getInput('comicname') .' - '.'GoComics';
    }
}
?>
