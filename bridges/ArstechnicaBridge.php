<?php
#ini_set('display_errors', 'On');
#error_reporting(E_ALL);
class ArstechnicaBridge extends BridgeAbstract {

        public function loadMetadatas() {

                $this->maintainer = "prysme";
                $this->name = "ArstechnicaBridge";
                $this->uri = "http://arstechnica.com";
                $this->description = "The PC enthusiast's resource. Power users and the tools they love, without computing religion";
                $this->update = "2016-08-09";

        }

        public function collectData(array $param) {
          function StripWithDelimiters($string, $start, $end) {
              while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
              } return $string;
          }
                function StripCDATA($string) {
                        $string = str_replace('<![CDATA[', '', $string);
                        $string = str_replace(']]>', '', $string);
                        return $string;
                }

                function ExtractContent($url) {
                  #echo $url;
                        $html2 = file_get_html($url);

                        $text = $html2->find("section[id='article-guts']", 0);
                        /*foreach ($text->find('<aside id="social-left">') as $node)
                            { $node = NULL; }*/
                        $text = StripWithDelimiters($text->innertext,'<aside id="social-left">','</aside>');
                        $text = StripWithDelimiters($text,'<figcaption class="caption">','</figcaption>');
                        $text = StripWithDelimiters($text,'<div class="gallery shortcode-gallery">','</div>');
                        //error_log("ICI", 0);
                        //error_log($text, 0);

                        return $text;
                }

                $html = $this->file_get_html('http://feeds.arstechnica.com/arstechnica/index') or $this->returnError('Could not request NextInpact.', 404);
                $limit = 0;

                foreach($html->find('item') as $element) {
                 if($limit < 5) {
                                $item = new \Item();
                                $item->title = StripCDATA($element->find('title', 0)->innertext);
                                $item->uri = StripCDATA($element->find('guid', 0)->plaintext);
                                $item->author = StripCDATA($element->find('author', 0)->innertext);
                                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                                $item->content = ExtractContent($item->uri);
                                //$item->content = $item->uri;
                                $this->items[] = $item;
                                $limit++;
                        }
                }

}

        public function getCacheDuration() {
                return 7200; // 2h
        }

}
