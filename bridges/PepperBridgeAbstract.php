<?php

class PepperBridgeAbstract extends BridgeAbstract
{
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        switch ($this->queriedContext) {
            case $this->i8n('context-keyword'):
                return $this->collectDataKeywords();
                break;
            case $this->i8n('context-group'):
                return $this->collectDataGroup();
                break;
            case $this->i8n('context-talk'):
                return $this->collectDataTalk();
                break;
        }
    }

    /**
     * Get the Deal data from the choosen group in the choosed order
     */
    protected function collectDataGroup()
    {
        $url = $this->getGroupURI();
        $this->collectDeals($url);
    }

    /**
     * Get the Deal data from the choosen keywords and parameters
     */
    protected function collectDataKeywords()
    {
        /* Even if the original website uses POST with the search page, GET works too */
        $url = $this->getSearchURI();
        $this->collectDeals($url);
    }

    /**
     * Get the Deal data using the given URL
     */
    protected function collectDeals($url)
    {
        $html = getSimpleHTMLDOM($url);
        $list = $html->find('article[id][class*=thread--deal]]');

        // Deal Description CSS Selector
        $selectorDescription = implode(
            ' ', /* Notice this is a space! */
            [
                'overflow--wrap-break'
            ]
        );

        // If there is no results, we don't parse the content because it display some random deals
        $noresult = $html->find('div[id=content-list]', 0)->find('h2', 0);
        if ($noresult !== null) {
            $this->items = [];
        } else {
            foreach ($list as $deal) {
                // Get the JSON Data stored as vue
                $jsonDealData = $this->getDealJsonData($deal);
                // DEPRECATED : website does not show this info in the deal list anymore
                // $dealMeta = Json::decode($deal->find('div[class=js-vue3]', 1)->getAttribute('data-vue3'));

                $item = [];
                $item['uri'] = $this->getDealURI($jsonDealData);
                $item['title'] = $this->getTitle($jsonDealData);
                $item['author'] = $this->getDealAuthor($jsonDealData);

                $item['content'] = '<table><tr><td><a href="'
                    . $item['uri']
                    . '">'
                    . $this->getImage($deal)
                    . '</td><td>'
                    . $this->getHTMLTitle($jsonDealData)
                    . $this->getPrice($jsonDealData)
                    . $this->getDiscount($jsonDealData)
                    /*
                     * DEPRECATED : the list does not show this info anymore
                     * . $this->getShipsFrom($dealMeta)
                     */
                    . $this->getShippingCost($jsonDealData)
                    . $this->getSource($jsonDealData)
                    . $this->getDealLocation($jsonDealData)
                    . $deal->find('div[class*=' . $selectorDescription . ']', 0)->innertext
                    . '</td><td>'
                    . $this->getTemperature($jsonDealData)
                    . '</td></table>';

                $item['timestamp'] = $this->getPublishedDate($jsonDealData);
                $this->items[] = $item;
            }
        }
    }

    /**
     * Get the Talk lastest comments
     */
    protected function collectDataTalk()
    {
        $threadURL = $this->getInput('url');
        $onlyWithUrl = $this->getInput('only_with_url');

        // Get Thread ID from url passed in parameter
        $threadSearch = preg_match('/-([0-9]{1,20})$/', $threadURL, $matches);

        // Show an error message if we can't find the thread ID in the URL sent by the user
        if ($threadSearch !== 1) {
            throwClientException($this->i8n('thread-error'));
        }
        $threadID = $matches[1];

        $url = $this->i8n('bridge-uri') . 'graphql';

        // Get Cookies header to do the query
        $cookiesHeaderValue = $this->getCookiesHeaderValue($url);

        // GraphQL String
        // This was extracted from https://www.dealabs.com/assets/js/modern/common_211b99.js
        // This string was extracted during a Website visit, and minified using this neat tool :
        // https://codepen.io/dangodev/pen/Baoqmoy
        $graphqlString = <<<'HEREDOC'
query comments($filter:CommentFilter!,$limit:Int,$page:Int){comments(filter:$filter,limit:$limit,page:$page){
items{...commentFields}pagination{...paginationFields}}}fragment commentFields on Comment{commentId threadId url 
preparedHtmlContent user{...userMediumAvatarFields...userNameFields...userPersonaFields bestBadge{...badgeFields}}
reactionCounts{type count}deletable currentUserReaction{type}reported reportable source status createdAt updatedAt 
ignored popular deletedBy{username}notes{content createdAt user{username}}lastEdit{reason timeAgo userId}}fragment 
userMediumAvatarFields on User{userId isDeletedOrPendingDeletion imageUrls(slot:"default",variations:
["user_small_avatar"])}fragment userNameFields on User{userId username isUserProfileHidden isDeletedOrPendingDeletion}
fragment userPersonaFields on User{persona{type text}}fragment badgeFields on Badge{badgeId level{...badgeLevelFields}}
fragment badgeLevelFields on BadgeLevel{key name description}fragment paginationFields on Pagination{count current last
 next previous size order}
HEREDOC;

        // Construct the JSON object to send to the Website
        $queryArray = [
            'query' => $graphqlString,
            'variables' => [
                'filter' => [
                    'threadId' => [
                        'eq' => $threadID,
                    ],
                    'order' => [
                        'direction' => 'Descending',
                    ],

                ],
                'page' => 1,
            ],
        ];
        $queryJSON = json_encode($queryArray);

        // HTTP headers
        $header = [
            'Content-Type: application/json',
            'Accept: application/json, text/plain, */*',
            'X-Pepper-Txn: threads.show',
            'X-Request-Type: application/vnd.pepper.v1+json',
            'X-Requested-With: XMLHttpRequest',
            "Cookie: $cookiesHeaderValue",
        ];
        // CURL Options
        $opts = [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $queryJSON
        ];
        $json = getContents($url, $header, $opts);
        $objects = json_decode($json);
        foreach ($objects->data->comments->items as $comment) {
            $item = [];
            $item['uri'] = $comment->url;
            $item['title'] = $comment->user->username . ' - ' . $comment->createdAt;
            $item['author'] = $comment->user->username;
            $item['content'] = $comment->preparedHtmlContent;
            $item['uid'] = $comment->commentId;
            // Timestamp handling needs a new parsing function
            if ($onlyWithUrl == true) {
                // Only parse the comment if it is not empry
                if ($item['content'] != '') {
                    // Count Links and Quote Links
                    $content = str_get_html($item['content']);
                    $countLinks = count($content->find('a[href]'));
                    $countQuoteLinks = count($content->find('a[href][class=userHtml-quote-source]'));
                    // Only add element if there are Links and more links tant Quote links
                    if ($countLinks > 0 && $countLinks > $countQuoteLinks) {
                        $this->items[] = $item;
                    }
                }
            } else {
                $this->items[] = $item;
            }
        }
    }

    private function getCookiesHeaderValue($url)
    {
        $response = getContents($url, [], [], true);
        $setCookieHeaders = $response->getHeader('set-cookie', true);
        $cookies = array_map(fn($c): string => explode(';', $c)[0], $setCookieHeaders);

        return implode('; ', $cookies);
    }

    /**
     * Check if the string $str contains any of the string of the array $arr
     * @return boolean true if the string matched anything otherwise false
     */
    private function contains($str, array $arr)
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the Price from a Deal if it exists
     * @return string String of the deal price
     */
    private function getPrice($jsonDealData)
    {
        if ($jsonDealData['props']['thread']['discountType'] == null) {
            $price = $jsonDealData['props']['thread']['price'];
                return '<div>' . $this->i8n('price') . ' : '
                . $price . ' ' . $this->i8n('currency') . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Get the Publish Date from a Deal if it exists
     * @return integer Timestamp of the published date of the deal
     */
    private function getPublishedDate($jsonDealData)
    {
        return $jsonDealData['props']['thread']['publishedAt'];
    }

    /**
     * Get the Deal Author from a Deal if it exists
     * @return String Author of the deal
     */
    private function getDealAuthor($jsonDealData)
    {
        return $jsonDealData['props']['thread']['user']['username'];
    }

    /**
     * Get the Title from a Deal if it exists
     * @return string String of the deal title
     */
    private function getTitle($jsonDealData)
    {
        $title = $jsonDealData['props']['thread']['title'];
        return $title;
    }

    /**
     * Get the Title from a Talk if it exists
     * @return string String of the Talk title
     */
    private function getTalkTitle()
    {
        $cacheKey = $this->getInput('url') . 'TITLE';
        $title = $this->loadCacheValue($cacheKey);
        // The cache does not contain the title of the bridge, we must get it and save it in the cache
        if ($title === null) {
            $html = getSimpleHTMLDOMCached($this->getInput('url'));
            $title = $html->find('title', 0)->plaintext;
            // Save the value in the cache for the next 15 days
            $this->saveCacheValue($cacheKey, $title, 86400 * 15);
        }
        return $title;
    }

    /**
     * Get the Title from a Group if it exists
     * @return string String of the Talk title
     */
    private function getGroupTitle()
    {
        $cacheKey = $this->getInput('group') . 'TITLE';
        $title = $this->loadCacheValue($cacheKey);
        // The cache does not contain the title of the bridge, we must get it and save it in the cache
        if ($title == null) {
            $html = getSimpleHTMLDOMCached($this->getGroupURI());
            // Search the title in the javascript mess
            preg_match('/threadGroupName":"([^"]*)","threadGroupUrlName":"' . $this->getInput('group') . '"/m', $html, $matches);
            $title = $matches[1];
            // Save the value in the cache for the next 15 days
            $this->saveCacheValue($cacheKey, $title, 86400 * 15);
        }

        $order = $this->getKey('order');
        return $title . ' - ' . $order;
    }

    /**
     * Get the HTML Title code from an item
     * @return string String of the deal title
     */
    private function getHTMLTitle($jsonDealData)
    {
        $html = '<h2><a href="' . $this->getDealURI($jsonDealData) . '">'
                . $this->getTitle($jsonDealData) . '</a></h2>';

        return $html;
    }

    /**
     * Get the URI from a Deal if it exists
     * @return string String of the deal URI
     */
    private function getDealURI($jsonDealData)
    {
        $dealSlug = $jsonDealData['props']['thread']['titleSlug'];
        $dealId = $jsonDealData['props']['thread']['threadId'];
        $uri = $this->i8n('bridge-uri') . $this->i8n('uri-deal') . $dealSlug . '-' . $dealId;
        return $uri;
    }

    /**
     * Get the Shipping costs from a Deal if it exists
     * @return string String of the deal shipping Cost
     */
    private function getShippingCost($jsonDealData)
    {
        $isFree = $jsonDealData['props']['thread']['shipping']['isFree'];
        $price = $jsonDealData['props']['thread']['shipping']['price'];
        if ($isFree !== null) {
                return '<div>' . $this->i8n('shipping') . ' : '
                    . $price . ' ' . $this->i8n('currency')
                    . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Get the temperature from a Deal if it exists
     * @return string String of the deal temperature
     */
    private function getTemperature($data)
    {
        return $data['props']['thread']['temperature'] . '°';
    }


    /**
     * Get the Deal data from the "data-vue2" JSON attribute
     * @return array Array containg the deal properties contained in the "data-vue2" attribute
     */
    private function getDealJsonData($deal)
    {
        $data = Json::decode($deal->find('div[class=js-vue3]', 0)->getAttribute('data-vue3'));
        return $data;
    }

    /**
     * Get the source of a Deal if it exists
     * @return string String of the deal source
     */
    private function getSource($jsonData)
    {
        if ($jsonData['props']['thread']['merchant'] != null) {
            $path = $this->i8n('uri-merchant') . $jsonData['props']['thread']['merchant']['merchantId'];
            $text = $jsonData['props']['thread']['merchant']['merchantName'];
            return '<div>' . $this->i8n('origin') . ' : <a href="' . static::URI . $path . '">' . $text . '</a></div>';
        } else {
            return '';
        }
    }

    /**
     * Get the original Price and discout from a Deal if it exists
     * @return string String of the deal original price and discount
     */
    private function getDiscount($jsonDealData)
    {
        $oldPrice = $jsonDealData['props']['thread']['nextBestPrice'];
        $newPrice = $jsonDealData['props']['thread']['price'];
        $percentage = $jsonDealData['props']['thread']['percentage'];

        if ($oldPrice != 0) {
            // If there is no percentage calculated, then calculate it manually
            if ($percentage == 0) {
                $percentage = round(100 - ($newPrice * 100 / $oldPrice), 2);
            }
            return '<div>' . $this->i8n('discount') . ' : <span style="text-decoration: line-through;">'
                . $oldPrice . ' ' . $this->i8n('currency')
                . '</span>&nbsp; -'
                . $percentage
                . ' %</div>';
        } else {
            return '';
        }
    }

    /**
     * Get the Deal location if it exists
     * @return string String of the deal location
     */
    private function getDealLocation($jsonDealData)
    {
        if ($jsonDealData['props']['thread']['isLocal']) {
            $content = '<div>' . $this->i8n('deal-type') . ' : ' . $this->i8n('localdeal') . '</div>';
        } else {
            $content = '';
        }
        return $content;
    }

    /**
     * Get the Picture URL from a Deal if it exists
     * @return string String of the deal Picture URL
     */
    private function getImage($deal)
    {
        // Get thread Image JSON content
        $content = Json::decode($deal->find('div[class=js-vue3]', 0)->getAttribute('data-vue3'));
        //return '<img src="' . $content['props']['threadImageUrl'] . '"/>';
        return '<img src="' . $this->i8n('image-host') . $content['props']['thread']['mainImage']['path'] . '/'
            . $content['props']['thread']['mainImage']['name'] . '/re/202x202/qt/70/'
            . $content['props']['thread']['mainImage']['uid'] . '"/>';
    }

    /**
     * Get the originating country from a Deal if it exists
     * @return string String of the deal originating country
     * DEPRECATED : the deal on the result list does not contain this info anymore
     */
    private function getShipsFrom($dealMeta)
    {
        $metas = $dealMeta['props']['metaRibbons'] ?? [];
        $shipsFrom = null;
        foreach ($metas as $meta) {
            if ($meta['type'] == 'dispatched-from') {
                $shipsFrom = $meta['text'];
            }
        }
        if ($shipsFrom != null) {
            return '<div>' . $shipsFrom . '</div>';
        }
        return '';
    }

    /**
     * Returns the RSS Feed title according to the parameters
     * @return string the RSS feed Tiyle
     */
    public function getName()
    {
        switch ($this->queriedContext) {
            case $this->i8n('context-keyword'):
                return $this->i8n('bridge-name') . ' - ' . $this->i8n('title-keyword') . ' : ' . $this->getInput('q');
                break;
            case $this->i8n('context-group'):
                return $this->i8n('bridge-name') . ' - ' . $this->i8n('title-group') . ' : ' . $this->getGroupTitle();
                break;
            case $this->i8n('context-talk'):
                return $this->i8n('bridge-name') . ' - ' . $this->i8n('title-talk') . ' : ' . $this->getTalkTitle();
                break;
            default: // Return default value
                return static::NAME;
        }
    }

    /**
     * Returns the RSS Feed URI according to the parameters
     * @return string the RSS feed Title
     */
    public function getURI()
    {
        switch ($this->queriedContext) {
            case $this->i8n('context-keyword'):
                return $this->getSearchURI();
                break;
            case $this->i8n('context-group'):
                return $this->getGroupURI();
                break;
            case $this->i8n('context-talk'):
                return $this->getTalkURI();
                break;
            default: // Return default value
                return static::URI;
        }
    }

    /**
     * Returns the RSS Feed URI for a keyword Feed
     * @return string the RSS feed URI
     */
    private function getSearchURI()
    {
        $q = $this->getInput('q');
        $hide_expired = $this->getInput('hide_expired');
        $hide_local = $this->getInput('hide_local');
        $priceFrom = $this->getInput('priceFrom');
        $priceTo = $this->getInput('priceTo');
        $url = $this->i8n('bridge-uri')
            . 'search?q='
            . urlencode($q)
            . '&hide_expired=' . $hide_expired
            . '&hide_local=' . $hide_local
            . '&priceFrom=' . $priceFrom
            . '&priceTo=' . $priceTo
            /* Some default parameters
             * search_fields : Search in Titres & Descriptions & Codes
             * sort_by : Sort the search by new deals
             * time_frame : Search will not be on a limited timeframe
             */
            . '&search_fields[]=1&search_fields[]=2&search_fields[]=3&sort_by=new&time_frame=0';
        return $url;
    }

    /**
     * Returns the RSS Feed URI for a group Feed
     * @return string the RSS feed URI
     */
    private function getGroupURI()
    {
        $group = $this->getInput('group');
        $order = $this->getInput('order');
        $subgroups = $this->getInput('subgroups');

        // This permit to keep the existing Feed to work
        if ($order == $this->i8n('context-hot')) {
            $sortBy = 'temp';
        } else if ($order == $this->i8n('context-new')) {
            $sortBy = 'new';
        }

        $url = $this->i8n('bridge-uri')
            . $this->i8n('uri-group') . $group . '?sortBy=' . $sortBy . '&groups=' . $subgroups;
        return $url;
    }

    /**
     * Returns the RSS Feed URI for a Talk Feed
     * @return string the RSS feed URI
     */
    private function getTalkURI()
    {
        $url = $this->getInput('url');
        return $url;
    }

    /**
     * This is some "localisation" function that returns the needed content using
     * the "$lang" class variable in the local class
     * @return various the local content needed
     */
    protected function i8n($key)
    {
        if (array_key_exists($key, $this->lang)) {
            return $this->lang[$key];
        } else {
            return null;
        }
    }
}
