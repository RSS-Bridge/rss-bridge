<?php
#ini_set('display_errors', 'On');
#error_reporting(E_ALL);
class ArstechnicaBridge extends BridgeAbstract {

        public function loadMetadatas() {

                $this->maintainer = "prysme";
                $this->name = "ArstechnicaBridge";
                $this->uri = "http://arstechnica.com";
                $this->description = "The PC enthusiast's resource. Power users and the tools they love, without computing religion";
                $this->update = "01/08/2016";

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
                        $text = StripWithDelimiters($text->innertext,'<aside id="social-left">','</aside>');
                        $text = StripWithDelimiters($text,'<div class="caption-credit">','</div>');
                        $text = StripWithDelimiters($text,'<div class="gallery shortcode-gallery">','</div>');
                        $text = StripWithDelimiters($text,'<div class="lSAction">','</div>');
                        $text = StripWithDelimiters($text,'<figcaption ','</figcaption>');
                        $text = StripWithDelimiters($text,'<li data-thumb','</li>');
                        //$text = strip_tags($text->innertext, '<p>');
                        #print_r("ICI");
                        #print_r($text);
                        #print_r("FIN");
                        return $text;
                }

                $html = $this->file_get_html('http://feeds.arstechnica.com/arstechnica/index') or $this->returnError('Could not request NextInpact.', 404);
                $limit = 0;

                foreach($html->find('item') as $element) {
                 if($limit < 5) {
                                $item = new \Item();
                                $item->title = StripCDATA($element->find('title', 0)->innertext);
                                $item->uri = StripCDATA($element->find('guid', 0)->plaintext);
                                $item->thumbnailUri = StripCDATA($element->find('enclosure', 0)->url);
                                $item->author = StripCDATA($element->find('author', 0)->innertext);
                                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                                $item->content = ExtractContent($item->uri);
                                //$item->content = $item->uri;
                                $this->items[] = $item;
                                $limit++;
                        }
                }

}


        public function getName() {
                return 'ArsTechnica';
        }

        public function getCacheDuration() {
                return 0; // 2h
        }

        public function getURI() {
                return "http://arstechnica.com";
        }

}
