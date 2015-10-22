<?php
/**
 * T411Bridge
 * Returns 5 newest torrents with specified search criteria
 *
 * @name T411
 * @homepage https://t411.io/
 * @description Returns the 5 newest torrents with specified search terms <br /> Use url part after '?' mark when using their search engine
 * @maintainer ORelio
 * @update 2015-09-05
 * @use1(search="search criteria")
 */
class T411Bridge extends BridgeAbstract {

    public function collectData(array $param) {

        //Utility function for extracting CDATA fields
        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        //Utility function for removing text based on specified delimiters
        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        //Ensure proper parameters have been provided
        if (empty($param['search'])) {
            $this->returnError('You must specify a search criteria', 400);
        }

        //Retrieve torrent listing as truncated rss, which does not contain torrent description
        $url = 'http://www.t411.io/torrents/rss/?'.$param['search'].'&order=added&type=desc';
        $html = file_get_html($url) or $this->returnError('Could not request t411: '.$url, 500);
        $limit = 0;

        //Process each item individually
        foreach($html->find('item') as $element) {

            //Limit total amount of requests
            if ($limit < 5) {

                //Requests are rate-limited
                sleep(1); //So we need to wait

                //Retrieve data from RSS entry
                $item_uri = StripCDATA($element->find('guid', 0)->plaintext);
                $item_title = StripWithDelimiters(StripCDATA($element->find('title', 0)->innertext), ' (S:', ')');
                $item_date = strtotime($element->find('pubDate', 0)->plaintext);

                //Retrieve full description from torrent page
                if ($item_html = file_get_html($item_uri)) {

                    //Retrieve data from page contents
                    $item_desc = $item_html->find('div.description', 0);
                    $item_author = $item_html->find('a.profile', 0)->innertext;

                    //Retrieve image for thumbnail or generic logo fallback
                    $item_image = 'http://www.t411.io/themes/blue/images/logo.png';
                    foreach ($item_desc->find('img') as $img) {
                        if (strpos($img->src, 'dreamprez') === false) {
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
                    $item->content = utf8_encode($item_desc->innertext);
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
        return 'https://t411.io';
    }

    public function getCacheDuration() {
        return 3600*3; // 3 hours
    }

}

