<?php
class T411Bridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'T411 Bridge';
    const URI = 'https://www.t411.li/';
    const DESCRIPTION = 'Returns the 10 newest torrents with specified search terms <br /> Use url part after "?" mark when using their search engine.';

    const PARAMETERS = array( array(
        'search'=>array(
            'name'=>'Search criteria',
            'required'=>true
        )
    ));

    public function collectData(){

        //Utility function for retrieving text based on start and end delimiters
        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        //Retrieve torrent listing from search results, which does not contain torrent description
        $url = self::URI.'torrents/search/?search='.urlencode($this->getInput('search')).'&order=added&type=desc';
        $html = getSimpleHTMLDOM($url)
          or returnServerError('Could not request t411: '.$url);

        $results = $html->find('table.results', 0);
        if (is_null($results))
            returnServerError('No results from t411: '.$url);
        $limit = 0;

        //Process each item individually
        foreach ($results->find('tr') as $element) {

            //Limit total amount of requests and ignore table header
            if ($limit >= 10){
              break;
            }
            if(is_object($element->find('th', 0))){
              continue;
            }

           //Requests are rate-limited
           usleep(500000); //So we need to wait (500ms)

           //Retrieve data from RSS entry
           $item_uri = self::URI.'torrents/details/?id='.ExtractFromDelimiters($element->find('a.nfo', 0)->outertext, '?id=', '"');
           $item_title = ExtractFromDelimiters($element->outertext, '" title="', '"');
           $item_date = strtotime($element->find('dd', 0)->plaintext);

           //Retrieve full description from torrent page
           $item_html = getSimpleHTMLDOM($item_uri);
           if (!$item_html) {
             continue;
           }

          //Retrieve data from page contents
          $item_desc = $item_html->find('div.description', 0);
          $item_author = $item_html->find('a.profile', 0)->innertext;

          //Cleanup advertisments
          $divs = explode('<div class="align-center">', $item_desc->innertext);
          $item_desc = '';
          foreach ($divs as $text)
              if (strpos($text, 'adprovider.adlure.net') === false)
                  $item_desc = $item_desc.'<div class="align-center">'.$text;
          $item_desc = preg_replace('/<h2 class="align-center">LIENS DE T..?L..?CHARGEMENT<\/h2>/i', '', $item_desc);

          //Build and add final item
          $item = array();
          $item['uri'] = $item_uri;
          $item['title'] = $item_title;
          $item['author'] = $item_author;
          $item['timestamp'] = $item_date;
          $item['content'] = $item_desc;
          $this->items[] = $item;
          $limit++;
        }
    }
}

