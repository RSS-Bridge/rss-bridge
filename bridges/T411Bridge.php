<?php
class T411Bridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = 'T411 Bridge';
        $this->uri = 'https://t411.ch/';
        $this->description = 'Returns the 10 newest torrents with specified search terms <br /> Use url part after "?" mark when using their search engine.';
        $this->update = '2016-08-09';

        $this->parameters[] =
        '[
            {
                "name" : "Search criteria",
                "identifier" : "search"
            }
        ]';
    }

    public function collectData(array $param) {

        //Utility function for retrieving text based on start and end delimiters
        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        //Ensure proper parameters have been provided
        if (empty($param['search'])) {
            $this->returnError('You must specify a search criteria', 400);
        }

        //Retrieve torrent listing from search results, which does not contain torrent description
        $url = $this->uri.'torrents/search/?'.$param['search'].'&order=added&type=desc';
        $html = $this->file_get_html($url) or $this->returnError('Could not request t411: '.$url, 500);
        $results = $html->find('table.results', 0);
        if (is_null($results))
            $this->returnError('No results from t411: '.$url, 500);
        $limit = 0;

        //Process each item individually
        foreach ($results->find('tr') as $element) {

            //Limit total amount of requests and ignore table header
            if ($limit < 10 && !is_object($element->find('th', 0))) {

                //Requests are rate-limited
                usleep(500000); //So we need to wait (500ms)

                //Retrieve data from RSS entry
                $item_uri = $this->uri.'torrents/details/?id='.ExtractFromDelimiters($element->find('a.nfo', 0)->outertext, '?id=', '"');
                $item_title = ExtractFromDelimiters($element->outertext, '" title="', '"');
                $item_date = strtotime($element->find('dd', 0)->plaintext);

                //Retrieve full description from torrent page
                if ($item_html = $this->file_get_html($item_uri)) {

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
                    $item = new \Item();
                    $item->uri = $item_uri;
                    $item->title = $item_title;
                    $item->author = $item_author;
                    $item->timestamp = $item_date;
                    $item->content = $item_desc;
                    $this->items[] = $item;
                    $limit++;
                }
            }
        }
    }
}

