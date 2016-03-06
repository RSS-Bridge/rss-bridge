<?php
class T411Bridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = "ORelio";
        $this->name = "T411";
        $this->uri = $this->getURI();
        $this->description = "Returns the 5 newest torrents with specified search terms <br /> Use url part after '?' mark when using their search engine";
        $this->update = "2016-02-06";

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
        $url = $this->getURI().'torrents/search/?'.$param['search'].'&order=added&type=desc';
        $html = file_get_html($url) or $this->returnError('Could not request t411: '.$url, 500);
        $results = $html->find('table.results', 0);
        if (is_null($results))
            $this->returnError('No results from t411: '.$url, 500);
        $limit = 0;

        //Process each item individually
        foreach($results->find('tr') as $element) {

            //Limit total amount of requests
            if ($limit < 10) {

                //Requests are rate-limited
                usleep(500000); //So we need to wait (500ms)

                //Retrieve data from RSS entry
                $item_uri = $this->getURI().'torrents/details/?id='.ExtractFromDelimiters($element->find('a.nfo', 0)->outertext, '?id=', '"');
                $item_title = ExtractFromDelimiters($element->outertext, '" title="', '"');
                $item_date = strtotime($element->find('dd', 0)->plaintext);

                //Retrieve full description from torrent page
                if ($item_html = file_get_html($item_uri)) {

                    //Retrieve data from page contents
                    $item_desc = $item_html->find('div.description', 0);
                    $item_author = $item_html->find('a.profile', 0)->innertext;

                    //Retrieve image for thumbnail or generic logo fallback
                    $item_image = $this->getURI().'themes/blue/images/logo.png';
                    foreach ($item_desc->find('img') as $img) {
                        if (strpos($img->src, 'prez') === false) {
                            $item_image = $img->src;
                            break;
                        }
                    }

                    //Build and add final item
                    $item = new \Item();
                    $item->uri = $item_uri;
                    $item->title = $item_title;
                    $item->author = $item_author;
                    $item->timestamp = $item_date;
                    $item->thumbnailUri = $item_image;
                    $item->content = $item_desc->innertext;
                    $this->items[] = $item;
                    $limit++;
                }
            }
        }
    }

    public function getName() {
        return "T411 Bridge";
    }

    public function getURI() {
        return 'https://t411.ch/';
    }

    public function getCacheDuration() {
        return 3600; // 1 hour
    }

}

