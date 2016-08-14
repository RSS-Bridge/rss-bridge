<?php
class ZDNetBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = 'ZDNet Bridge';
        $this->uri = 'http://www.zdnet.com/';
        $this->description = 'Technology News, Analysis, Comments and Product Reviews for IT Professionals.';
        $this->update = '2016-08-09';

        $this->parameters[] =
        // http://www.zdnet.com/zdnet.opml
        '[
            {
                "name" : "Feed",
                "type" : "list",
                "identifier" : "feed",
                "values" :
                [
                    { "name" : "---- Select ----", "value" : "" },

                    { "name" : "", "value" : "" },
                    { "name" : "Subscribe to ZDNet RSS Feeds", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;All Blogs", "value" : "blog" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Just News", "value" : "news" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;All Reviews", "value" : "topic/reviews" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest Downloads", "value" : "downloads!recent" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest Articles", "value" : "/" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest Australia Articles", "value" : "au" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest UK Articles", "value" : "uk" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest US Articles", "value" : "us" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Latest Asia Articles", "value" : "as" },

                    { "name" : "", "value" : "" },
                    { "name" : "Keep up with ZDNet Blogs RSS:", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Transforming the Datacenter", "value" : "blog/transforming-datacenter" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;SMB India", "value" : "blog/smb-india" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Indonesia BizTech", "value" : "blog/indonesia-biztech" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Hong Kong Techie", "value" : "blog/hong-kong-techie" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Tech Taiwan", "value" : "blog/tech-taiwan" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Startup India", "value" : "blog/startup-india" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Starting Up Asia", "value" : "blog/starting-up-asia" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Next-Gen Partner", "value" : "blog/partner" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Post-PC Developments", "value" : "blog/post-pc" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Benelux", "value" : "blog/benelux" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Heat Sink", "value" : "blog/heat-sink" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Italy&#039;s got tech", "value" : "blog/italy" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;African Enterprise", "value" : "blog/african-enterprise" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;New Tech for Old India", "value" : "blog/new-india" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Estonia Uncovered", "value" : "blog/estonia" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;IT Iberia", "value" : "blog/iberia" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Brazil Tech", "value" : "blog/brazil" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;500 words into the future", "value" : "blog/500-words-into-the-future" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ÜberTech", "value" : "blog/ubertech" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;All About Microsoft", "value" : "blog/microsoft" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Back office", "value" : "blog/back-office" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Barker Bites Back", "value" : "blog/barker-bites-back" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Between the Lines", "value" : "blog/btl" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Big on Data", "value" : "blog/big-data" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;bootstrappr", "value" : "blog/bootstrappr" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;By The Way", "value" : "blog/by-the-way" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Central European Processing", "value" : "blog/central-europe" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Cloud Builders", "value" : "blog/cloud-builders" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Communication Breakdown", "value" : "blog/communication-breakdown" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Collaboration 2.0", "value" : "blog/collaboration" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Constellation Research", "value" : "blog/constellation" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Consumerization: BYOD", "value" : "blog/consumerization" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;DIY-IT", "value" : "blog/diy-it" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Enterprise Web 2.0", "value" : "blog/hinchcliffe" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Five Nines: The Next Gen Datacenter", "value" : "blog/datacenter" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Forrester Research", "value" : "blog/forrester" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Full Duplex", "value" : "blog/full-duplex" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Gen Why?", "value" : "blog/gen-why" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Hardware 2.0", "value" : "blog/hardware" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Identity Matters", "value" : "blog/identity" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;iGeneration", "value" : "blog/igeneration" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Internet of Everything", "value" : "blog/cisco" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Beyond IT Failure", "value" : "blog/projectfailures" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Jamie&#039;s Mostly Linux Stuff", "value" : "blog/jamies-mostly-linux-stuff" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Jack&#039;s Blog", "value" : "blog/jacks-blog" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Laptops &amp; Desktops", "value" : "blog/computers" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Linux and Open Source", "value" : "blog/open-source" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;London Calling", "value" : "blog/london" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mapping Babel", "value" : "blog/mapping-babel" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mixed Signals", "value" : "blog/mixed-signals" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mobile India", "value" : "blog/mobile-india" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mobile News", "value" : "blog/mobile-news" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Networking", "value" : "blog/networking" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Norse Code", "value" : "blog/norse-code" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Null Pointer", "value" : "blog/null-pointer" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The Full Tilt", "value" : "blog/the-full-tilt" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Pinoy Post", "value" : "blog/pinoy-post" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Practically Tech", "value" : "blog/practically-tech" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Product Central", "value" : "blog/product-central" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Pulp Tech", "value" : "blog/violetblue" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Qubits and Pieces", "value" : "blog/qubits-and-pieces" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Securify This!", "value" : "blog/securify-this" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Service Oriented", "value" : "blog/service-oriented" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Small Talk", "value" : "blog/small-talk" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Small Business Matters", "value" : "blog/small-business-matters" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Smartphones and Cell Phones", "value" : "blog/cell-phones" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Social Business", "value" : "blog/feeds" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Social CRM: The Conversation", "value" : "blog/crm" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Software &amp; Services Safari", "value" : "blog/sommer" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Storage Bits", "value" : "blog/storage" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Stacking up Open Clouds", "value" : "blog/apac-redhat" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Techie Isles", "value" : "blog/techie-isles" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Technolatte", "value" : "blog/technolatte" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Tech Podium", "value" : "blog/tech-podium" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Tel Aviv Tech", "value" : "blog/tel-aviv" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Tech Broiler", "value" : "blog/perlow" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The SANMAN", "value" : "blog/the-sanman" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The open source revolution", "value" : "blog/the-open-source-revolution" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The German View", "value" : "blog/german" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The Ed Bott Report", "value" : "blog/bott" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The Mobile Gadgeteer", "value" : "blog/mobile-gadgeteer" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The Apple Core", "value" : "blog/apple" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Tom Foremski: IMHO", "value" : "blog/foremski" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Twisted Wire", "value" : "blog/twisted-wire" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Vive la tech", "value" : "blog/france" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Virtually Speaking", "value" : "blog/virtualization" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;View from China", "value" : "blog/china" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Web design &amp; Free Software", "value" : "blog/web-design-and-free-software" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet Government", "value" : "blog/government" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet UK Book Reviews", "value" : "blog/zdnet-uk-book-reviews" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet UK First Take", "value" : "blog/zdnet-uk-first-take" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Zero Day", "value" : "blog/security" },

                    { "name" : "", "value" : "" },
                    { "name" : "ZDNet Hot Topics RSS:", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Apple", "value" : "topic/apple" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Collaboration", "value" : "topic/collaboration" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Enterprise Software", "value" : "topic/enterprise-software" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Google", "value" : "topic/google" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Great debate", "value" : "topic/great-debate" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Hardware", "value" : "topic/hardware" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;IBM", "value" : "topic/ibm" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;iOS", "value" : "topic/ios" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;iPhone", "value" : "topic/iphone" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;iPad", "value" : "topic/ipad" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;IT Priorities", "value" : "topic/it-priorities" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Laptops", "value" : "topic/laptops" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Legal", "value" : "topic/legal" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Linux", "value" : "topic/linux" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Microsoft", "value" : "topic/microsoft" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mobile OS", "value" : "topic/mobile-os" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Mobility", "value" : "topic/mobility" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Networking", "value" : "topic/networking" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Oracle", "value" : "topic/oracle" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Processors", "value" : "topic/processors" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Samsung", "value" : "topic/samsung" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Security", "value" : "topic/security" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Small business: going big on mobility", "value" : "topic/small-business-going-big-on-mobility" },

                    { "name" : "", "value" : "" },
                    { "name" : "Product Blogs:", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Digital Cameras &amp; Camcorders", "value" : "blog/digitalcameras" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Home Theater", "value" : "blog/home-theater" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Laptops and Desktops", "value" : "blog/computers" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The Mobile Gadgeteer", "value" : "blog/mobile-gadgeteer" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Smartphones and Cell Phones", "value" : "blog/cell-phones" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;The ToyBox", "value" : "blog/gadgetreviews" },

                    { "name" : "", "value" : "" },
                    { "name" : "Vertical Blogs:", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet Education", "value" : "blog/education" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet Healthcare", "value" : "blog/healthcare" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;ZDNet Government", "value" : "blog/government" }
                ]
            }
        ]';

    }

    public function collectData(array $param) {

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return trim($string);
        }

        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function StripRecursiveHTMLSection($string, $tag_name, $tag_start) {
            $open_tag = '<'.$tag_name;
            $close_tag = '</'.$tag_name.'>';
            $close_tag_length = strlen($close_tag);
            if (strpos($tag_start, $open_tag) === 0) {
                while (strpos($string, $tag_start) !== false) {
                    $max_recursion = 100;
                    $section_to_remove = null;
                    $section_start = strpos($string, $tag_start);
                    $search_offset = $section_start;
                    do {
                        $max_recursion--;
                        $section_end = strpos($string, $close_tag, $search_offset);
                        $search_offset = $section_end + $close_tag_length;
                        $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                        $open_tag_count = substr_count($section_to_remove, $open_tag);
                        $close_tag_count = substr_count($section_to_remove, $close_tag);
                    } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
                    $string = str_replace($section_to_remove, '', $string);
                }
            }
            return $string;
        }

        $baseUri = $this->getURI();
        $feed = $param['feed'];
        if (empty($feed))
            $this->returnError('Please select a feed to display.', 400);
        if (strpos($feed, 'downloads!') !== false) {
            $feed = str_replace('downloads!', '', $feed);
            $baseUri = str_replace('www.', 'downloads.', $baseUri);
        }
        if ($feed !== preg_replace('/[^a-zA-Z0-9-\/]+/', '', $feed) || substr_count($feed, '/') > 1 || strlen($feed > 64))
            $this->returnError('Invalid "feed" parameter.', 400);
        $url = $baseUri.trim($feed, '/').'/rss.xml';
        $html = $this->file_get_html($url) or $this->returnError('Could not request ZDNet: '.$url, 500);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 10) {
                $article_url = preg_replace('/([^#]+)#ftag=.*/', '$1', StripCDATA(ExtractFromDelimiters($element->innertext, '<link>', '</link>')));
                $article_author = StripCDATA(ExtractFromDelimiters($element->innertext, 'role="author">', '<'));
                $article_title = StripCDATA($element->find('title', 0)->plaintext);
                $article_subtitle = StripCDATA($element->find('description', 0)->plaintext);
                $article_timestamp = strtotime(StripCDATA($element->find('pubDate', 0)->plaintext));
                $article = $this->file_get_html($article_url) or $this->returnError('Could not request ZDNet: '.$article_url, 500);

                if (!empty($article_author))
                    $author = $article_author;
                else {
                    $author = $article->find('meta[name=author]', 0);
                    if (is_object($author))
                        $author = $author->content;
                    else $author = 'ZDNet';
                }

                $thumbnail = $article->find('meta[itemprop=image]', 0);
                if (is_object($thumbnail))
                    $thumbnail = $thumbnail->content;
                else $thumbnail = '';

                $contents = $article->find('article', 0)->innertext;
                foreach (array(
                    '<div class="shareBar"',
                    '<div class="shortcodeGalleryWrapper"',
                    '<div class="relatedContent',
                    '<div class="downloadNow',
                    '<div data-shortcode',
                    '<div id="sharethrough',
                    '<div id="inpage-video'
                ) as $div_start) {
                    $contents = StripRecursiveHTMLSection($contents , 'div', $div_start);
                }
                $contents = StripWithDelimiters($contents, '<script', '</script>');
                $contents = StripWithDelimiters($contents, '<meta itemprop="image"', '>');
                $contents = trim(StripWithDelimiters($contents, '<section class="sharethrough-top', '</section>'));
                $content_img = strpos($contents, '<img');         //Look for first image
                if (($content_img !== false && $content_img < 512) || $thumbnail == '')
                    $content_img = ''; //Image already present on article beginning or no thumbnail
                else $content_img = '<p><img src="'.$thumbnail.'" /></p>'; //Include thumbnail
                $contents = $content_img
                    .'<p><b>'.$article_subtitle.'</b></p>'
                    .$contents;

                $item = new \Item();
                $item->author = $author;
                $item->uri = $article_url;
                $item->title = $article_title;
                $item->timestamp = $article_timestamp;
                $item->content = $contents;
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}
