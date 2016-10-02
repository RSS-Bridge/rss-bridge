<?php
class ZDNetBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'ZDNet Bridge';
    const URI = 'http://www.zdnet.com/';
    const DESCRIPTION = 'Technology News, Analysis, Comments and Product Reviews for IT Professionals.';

    //http://www.zdnet.com/zdnet.opml
    const PARAMETERS = array( array(
        'feed'=>array(
            'name'=>'Feed',
            'type'=>'list',
            'values'=>array(
                'Subscribe to ZDNet RSS Feeds'=>array(
                    'All Blogs'=>'blog',
                    'Just News'=>'news',
                    'All Reviews'=>'topic/reviews',
                    'Latest Downloads'=>'downloads!recent',
                    'Latest Articles'=>'/',
                    'Latest Australia Articles'=>'au',
                    'Latest UK Articles'=>'uk',
                    'Latest US Articles'=>'us',
                    'Latest Asia Articles'=>'as'
                ),
                'Keep up with ZDNet Blogs RSS:'=>array(
                    'Transforming the Datacenter'=>'blog/transforming-datacenter',
                    'SMB India'=>'blog/smb-india',
                    'Indonesia BizTech'=>'blog/indonesia-biztech',
                    'Hong Kong Techie'=>'blog/hong-kong-techie',
                    'Tech Taiwan'=>'blog/tech-taiwan',
                    'Startup India'=>'blog/startup-india',
                    'Starting Up Asia'=>'blog/starting-up-asia',
                    'Next-Gen Partner'=>'blog/partner',
                    'Post-PC Developments'=>'blog/post-pc',
                    'Benelux'=>'blog/benelux',
                    'Heat Sink'=>'blog/heat-sink',
                    'Italy\'s got tech'=>'blog/italy',
                    'African Enterprise'=>'blog/african-enterprise',
                    'New Tech for Old India'=>'blog/new-india',
                    'Estonia Uncovered'=>'blog/estonia',
                    'IT Iberia'=>'blog/iberia',
                    'Brazil Tech'=>'blog/brazil',
                    '500 words into the future'=>'blog/500-words-into-the-future',
                    'ÜberTech'=>'blog/ubertech',
                    'All About Microsoft'=>'blog/microsoft',
                    'Back office'=>'blog/back-office',
                    'Barker Bites Back'=>'blog/barker-bites-back',
                    'Between the Lines'=>'blog/btl',
                    'Big on Data'=>'blog/big-data',
                    'bootstrappr'=>'blog/bootstrappr',
                    'By The Way'=>'blog/by-the-way',
                    'Central European Processing'=>'blog/central-europe',
                    'Cloud Builders'=>'blog/cloud-builders',
                    'Communication Breakdown'=>'blog/communication-breakdown',
                    'Collaboration 2.0'=>'blog/collaboration',
                    'Constellation Research'=>'blog/constellation',
                    'Consumerization: BYOD'=>'blog/consumerization',
                    'DIY-IT'=>'blog/diy-it',
                    'Enterprise Web 2.0'=>'blog/hinchcliffe',
                    'Five Nines: The Next Gen Datacenter'=>'blog/datacenter',
                    'Forrester Research'=>'blog/forrester',
                    'Full Duplex'=>'blog/full-duplex',
                    'Gen Why?'=>'blog/gen-why',
                    'Hardware 2.0'=>'blog/hardware',
                    'Identity Matters'=>'blog/identity',
                    'iGeneration'=>'blog/igeneration',
                    'Internet of Everything'=>'blog/cisco',
                    'Beyond IT Failure'=>'blog/projectfailures',
                    'Jamie\'s Mostly Linux Stuff'=>'blog/jamies-mostly-linux-stuff',
                    'Jack\'s Blog'=>'blog/jacks-blog',
                    'Laptops & Desktops'=>'blog/computers',
                    'Linux and Open Source'=>'blog/open-source',
                    'London Calling'=>'blog/london',
                    'Mapping Babel'=>'blog/mapping-babel',
                    'Mixed Signals'=>'blog/mixed-signals',
                    'Mobile India'=>'blog/mobile-india',
                    'Mobile News'=>'blog/mobile-news',
                    'Networking'=>'blog/networking',
                    'Norse Code'=>'blog/norse-code',
                    'Null Pointer'=>'blog/null-pointer',
                    'The Full Tilt'=>'blog/the-full-tilt',
                    'Pinoy Post'=>'blog/pinoy-post',
                    'Practically Tech'=>'blog/practically-tech',
                    'Product Central'=>'blog/product-central',
                    'Pulp Tech'=>'blog/violetblue',
                    'Qubits and Pieces'=>'blog/qubits-and-pieces',
                    'Securify This!'=>'blog/securify-this',
                    'Service Oriented'=>'blog/service-oriented',
                    'Small Talk'=>'blog/small-talk',
                    'Small Business Matters'=>'blog/small-business-matters',
                    'Smartphones and Cell Phones'=>'blog/cell-phones',
                    'Social Business'=>'blog/feeds',
                    'Social CRM: The Conversation'=>'blog/crm',
                    'Software & Services Safari'=>'blog/sommer',
                    'Storage Bits'=>'blog/storage',
                    'Stacking up Open Clouds'=>'blog/apac-redhat',
                    'Techie Isles'=>'blog/techie-isles',
                    'Technolatte'=>'blog/technolatte',
                    'Tech Podium'=>'blog/tech-podium',
                    'Tel Aviv Tech'=>'blog/tel-aviv',
                    'Tech Broiler'=>'blog/perlow',
                    'The SANMAN'=>'blog/the-sanman',
                    'The open source revolution'=>'blog/the-open-source-revolution',
                    'The German View'=>'blog/german',
                    'The Ed Bott Report'=>'blog/bott',
                    'The Mobile Gadgeteer'=>'blog/mobile-gadgeteer',
                    'The Apple Core'=>'blog/apple',
                    'Tom Foremski: IMHO'=>'blog/foremski',
                    'Twisted Wire'=>'blog/twisted-wire',
                    'Vive la tech'=>'blog/france',
                    'Virtually Speaking'=>'blog/virtualization',
                    'View from China'=>'blog/china',
                    'Web design & Free Software'=>'blog/web-design-and-free-software',
                    'ZDNet Government'=>'blog/government',
                    'ZDNet UK Book Reviews'=>'blog/zdnet-uk-book-reviews',
                    'ZDNet UK First Take'=>'blog/zdnet-uk-first-take',
                    'Zero Day'=>'blog/security'
                ),
                'ZDNet Hot Topics RSS:'=>array(
                    'Apple'=>'topic/apple',
                    'Collaboration'=>'topic/collaboration',
                    'Enterprise Software'=>'topic/enterprise-software',
                    'Google'=>'topic/google',
                    'Great debate'=>'topic/great-debate',
                    'Hardware'=>'topic/hardware',
                    'IBM'=>'topic/ibm',
                    'iOS'=>'topic/ios',
                    'iPhone'=>'topic/iphone',
                    'iPad'=>'topic/ipad',
                    'IT Priorities'=>'topic/it-priorities',
                    'Laptops'=>'topic/laptops',
                    'Legal'=>'topic/legal',
                    'Linux'=>'topic/linux',
                    'Microsoft'=>'topic/microsoft',
                    'Mobile OS'=>'topic/mobile-os',
                    'Mobility'=>'topic/mobility',
                    'Networking'=>'topic/networking',
                    'Oracle'=>'topic/oracle',
                    'Processors'=>'topic/processors',
                    'Samsung'=>'topic/samsung',
                    'Security'=>'topic/security',
                    'Small business: going big on mobility'=>'topic/small-business-going-big-on-mobility'
                ),
                'Product Blogs:'=>array(
                    'Digital Cameras & Camcorders'=>'blog/digitalcameras',
                    'Home Theater'=>'blog/home-theater',
                    'Laptops and Desktops'=>'blog/computers',
                    'The Mobile Gadgeteer'=>'blog/mobile-gadgeteer',
                    'Smartphones and Cell Phones'=>'blog/cell-phones',
                    'The ToyBox'=>'blog/gadgetreviews'
                ),
                'Vertical Blogs:'=>array(
                    'ZDNet Education'=>'blog/education',
                    'ZDNet Healthcare'=>'blog/healthcare',
                    'ZDNet Government'=>'blog/government'
                )
            )
        )
    ));

    public function collectData(){

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

        $baseUri = self::URI;
        $feed = $this->getInput('feed');
        if (strpos($feed, 'downloads!') !== false) {
            $feed = str_replace('downloads!', '', $feed);
            $baseUri = str_replace('www.', 'downloads.', $baseUri);
        }
        $url = $baseUri.trim($feed, '/').'/rss.xml';
        $html = getSimpleHTMLDOM($url) or returnServerError('Could not request ZDNet: '.$url);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 10) {
                $article_url = preg_replace('/([^#]+)#ftag=.*/', '$1', StripCDATA(ExtractFromDelimiters($element->innertext, '<link>', '</link>')));
                $article_author = StripCDATA(ExtractFromDelimiters($element->innertext, 'role="author">', '<'));
                $article_title = StripCDATA($element->find('title', 0)->plaintext);
                $article_subtitle = StripCDATA($element->find('description', 0)->plaintext);
                $article_timestamp = strtotime(StripCDATA($element->find('pubDate', 0)->plaintext));
                $article = getSimpleHTMLDOM($article_url) or returnServerError('Could not request ZDNet: '.$article_url);

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

                $item = array();
                $item['author'] = $author;
                $item['uri'] = $article_url;
                $item['title'] = $article_title;
                $item['timestamp'] = $article_timestamp;
                $item['content'] = $contents;
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}
