<?php

class ZDNetBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
    const NAME = 'ZDNet Bridge';
    const URI = 'https://www.zdnet.com/';
    const DESCRIPTION = 'Technology News, Analysis, Comments and Product Reviews for IT Professionals.';

    //http://www.zdnet.com/zdnet.opml
    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'Subscribe to ZDNet RSS Feeds' => [
                    'All Blogs' => 'blog',
                    'Just News' => 'news',
                    'All Reviews' => 'topic/reviews',
                    'Latest Downloads' => 'downloads!recent',
                    'Latest Articles' => '/',
                    'Latest Australia Articles' => 'au',
                    'Latest UK Articles' => 'uk',
                    'Latest US Articles' => 'us',
                    'Latest Asia Articles' => 'as'
                ],
                'Keep up with ZDNet Blogs RSS:' => [
                    'Transforming the Datacenter' => 'blog/transforming-datacenter',
                    'SMB India' => 'blog/smb-india',
                    'Indonesia BizTech' => 'blog/indonesia-biztech',
                    'Hong Kong Techie' => 'blog/hong-kong-techie',
                    'Tech Taiwan' => 'blog/tech-taiwan',
                    'Startup India' => 'blog/startup-india',
                    'Starting Up Asia' => 'blog/starting-up-asia',
                    'Next-Gen Partner' => 'blog/partner',
                    'Post-PC Developments' => 'blog/post-pc',
                    'Benelux' => 'blog/benelux',
                    'Heat Sink' => 'blog/heat-sink',
                    'Italy\'s got tech' => 'blog/italy',
                    'African Enterprise' => 'blog/african-enterprise',
                    'New Tech for Old India' => 'blog/new-india',
                    'Estonia Uncovered' => 'blog/estonia',
                    'IT Iberia' => 'blog/iberia',
                    'Brazil Tech' => 'blog/brazil',
                    '500 words into the future' => 'blog/500-words-into-the-future',
                    'ÜberTech' => 'blog/ubertech',
                    'All About Microsoft' => 'blog/microsoft',
                    'Back office' => 'blog/back-office',
                    'Barker Bites Back' => 'blog/barker-bites-back',
                    'Between the Lines' => 'blog/btl',
                    'Big on Data' => 'blog/big-data',
                    'bootstrappr' => 'blog/bootstrappr',
                    'By The Way' => 'blog/by-the-way',
                    'Central European Processing' => 'blog/central-europe',
                    'Cloud Builders' => 'blog/cloud-builders',
                    'Communication Breakdown' => 'blog/communication-breakdown',
                    'Collaboration 2.0' => 'blog/collaboration',
                    'Constellation Research' => 'blog/constellation',
                    'Consumerization: BYOD' => 'blog/consumerization',
                    'DIY-IT' => 'blog/diy-it',
                    'Enterprise Web 2.0' => 'blog/hinchcliffe',
                    'Five Nines: The Next Gen Datacenter' => 'blog/datacenter',
                    'Forrester Research' => 'blog/forrester',
                    'Full Duplex' => 'blog/full-duplex',
                    'Gen Why?' => 'blog/gen-why',
                    'Hardware 2.0' => 'blog/hardware',
                    'Identity Matters' => 'blog/identity',
                    'iGeneration' => 'blog/igeneration',
                    'Internet of Everything' => 'blog/cisco',
                    'Beyond IT Failure' => 'blog/projectfailures',
                    'Jamie\'s Mostly Linux Stuff' => 'blog/jamies-mostly-linux-stuff',
                    'Jack\'s Blog' => 'blog/jacks-blog',
                    'Laptops & Desktops' => 'blog/computers',
                    'Linux and Open Source' => 'blog/open-source',
                    'London Calling' => 'blog/london',
                    'Mapping Babel' => 'blog/mapping-babel',
                    'Mixed Signals' => 'blog/mixed-signals',
                    'Mobile India' => 'blog/mobile-india',
                    'Mobile News' => 'blog/mobile-news',
                    'Networking' => 'blog/networking',
                    'Norse Code' => 'blog/norse-code',
                    'Null Pointer' => 'blog/null-pointer',
                    'The Full Tilt' => 'blog/the-full-tilt',
                    'Pinoy Post' => 'blog/pinoy-post',
                    'Practically Tech' => 'blog/practically-tech',
                    'Product Central' => 'blog/product-central',
                    'Pulp Tech' => 'blog/violetblue',
                    'Qubits and Pieces' => 'blog/qubits-and-pieces',
                    'Securify This!' => 'blog/securify-this',
                    'Service Oriented' => 'blog/service-oriented',
                    'Small Talk' => 'blog/small-talk',
                    'Small Business Matters' => 'blog/small-business-matters',
                    'Smartphones and Cell Phones' => 'blog/cell-phones',
                    'Social Business' => 'blog/feeds',
                    'Social CRM: The Conversation' => 'blog/crm',
                    'Software & Services Safari' => 'blog/sommer',
                    'Storage Bits' => 'blog/storage',
                    'Stacking up Open Clouds' => 'blog/apac-redhat',
                    'Techie Isles' => 'blog/techie-isles',
                    'Technolatte' => 'blog/technolatte',
                    'Tech Podium' => 'blog/tech-podium',
                    'Tel Aviv Tech' => 'blog/tel-aviv',
                    'Tech Broiler' => 'blog/perlow',
                    'The SANMAN' => 'blog/the-sanman',
                    'The open source revolution' => 'blog/the-open-source-revolution',
                    'The German View' => 'blog/german',
                    'The Ed Bott Report' => 'blog/bott',
                    'The Mobile Gadgeteer' => 'blog/mobile-gadgeteer',
                    'The Apple Core' => 'blog/apple',
                    'Tom Foremski: IMHO' => 'blog/foremski',
                    'Twisted Wire' => 'blog/twisted-wire',
                    'Vive la tech' => 'blog/france',
                    'Virtually Speaking' => 'blog/virtualization',
                    'View from China' => 'blog/china',
                    'Web design & Free Software' => 'blog/web-design-and-free-software',
                    'ZDNet Government' => 'blog/government',
                    'ZDNet UK Book Reviews' => 'blog/zdnet-uk-book-reviews',
                    'ZDNet UK First Take' => 'blog/zdnet-uk-first-take',
                    'Zero Day' => 'blog/security'
                ],
                'ZDNet Hot Topics RSS:' => [
                    'Apple' => 'topic/apple',
                    'Collaboration' => 'topic/collaboration',
                    'Enterprise Software' => 'topic/enterprise-software',
                    'Google' => 'topic/google',
                    'Great debate' => 'topic/great-debate',
                    'Hardware' => 'topic/hardware',
                    'IBM' => 'topic/ibm',
                    'iOS' => 'topic/ios',
                    'iPhone' => 'topic/iphone',
                    'iPad' => 'topic/ipad',
                    'IT Priorities' => 'topic/it-priorities',
                    'Laptops' => 'topic/laptops',
                    'Legal' => 'topic/legal',
                    'Linux' => 'topic/linux',
                    'Microsoft' => 'topic/microsoft',
                    'Mobile OS' => 'topic/mobile-os',
                    'Mobility' => 'topic/mobility',
                    'Networking' => 'topic/networking',
                    'Oracle' => 'topic/oracle',
                    'Processors' => 'topic/processors',
                    'Samsung' => 'topic/samsung',
                    'Security' => 'topic/security',
                    'Small business: going big on mobility' => 'topic/small-business-going-big-on-mobility'
                ],
                'Product Blogs:' => [
                    'Digital Cameras & Camcorders' => 'blog/digitalcameras',
                    'Home Theater' => 'blog/home-theater',
                    'Laptops and Desktops' => 'blog/computers',
                    'The Mobile Gadgeteer' => 'blog/mobile-gadgeteer',
                    'Smartphones and Cell Phones' => 'blog/cell-phones',
                    'The ToyBox' => 'blog/gadgetreviews'
                ],
                'Vertical Blogs:' => [
                    'ZDNet Education' => 'blog/education',
                    'ZDNet Healthcare' => 'blog/healthcare',
                    'ZDNet Government' => 'blog/government'
                ]
            ]
        ],
        'limit' => self::LIMIT,
    ]];

    public function collectData()
    {
        $baseUri = static::URI;
        $feed = $this->getInput('feed');
        if (strpos($feed, 'downloads!') !== false) {
            $feed = str_replace('downloads!', '', $feed);
            $baseUri = str_replace('www.', 'downloads.', $baseUri);
        }
        $url = $baseUri . trim($feed, '/') . '/rss.xml';
        $limit = $this->getInput('limit') ?? 10;
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem($item)
    {
        $item = parent::parseItem($item);

        $article = getSimpleHTMLDOMCached($item['uri']);
        if (!$article) {
            returnServerError('Could not request ZDNet: ' . $url);
        }

        $contents = $article->find('article', 0)->innertext;
        foreach (
            [
            '<div class="shareBar"',
            '<div class="shortcodeGalleryWrapper"',
            '<div class="relatedContent',
            '<div class="downloadNow',
            '<div data-shortcode',
            '<div id="sharethrough',
            '<div id="inpage-video',
            '<div class="share-bar-wrapper"',
            ] as $div_start
        ) {
            $contents = stripRecursiveHtmlSection($contents, 'div', $div_start);
        }
        $contents = stripWithDelimiters($contents, '<script', '</script>');
        $contents = stripWithDelimiters($contents, '<meta itemprop="image"', '>');
        $contents = stripWithDelimiters($contents, '<svg class="svg-symbol', '</svg>');
        $contents = trim(stripWithDelimiters($contents, '<section class="sharethrough-top', '</section>'));
        $item['content'] = $contents;

        return $item;
    }
}
