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
        $list = $html->find('article[id]');

        // Deal Image Link CSS Selector
        $selectorImageLink = implode(
            ' ', /* Notice this is a space! */
            [
                'cept-thread-image-link',
                'imgFrame',
                'imgFrame--noBorder',
                'thread-listImgCell',
            ]
        );

        // Deal Link CSS Selector
        $selectorLink = implode(
            ' ', /* Notice this is a space! */
            [
                'cept-tt',
                'thread-link',
                'linkPlain',
            ]
        );

        // Deal Hotness CSS Selector
        $selectorHot = implode(
            ' ', /* Notice this is a space! */
            [
                'vote-box'
            ]
        );

        // Deal Description CSS Selector
        $selectorDescription = implode(
            ' ', /* Notice this is a space! */
            [
                'overflow--wrap-break'
            ]
        );

        // Deal Date CSS Selector
        $selectorDate = implode(
            ' ', /* Notice this is a space! */
            [
                'size--all-s',
                'flex',
                'boxAlign-jc--all-fe'
            ]
        );

        // If there is no results, we don't parse the content because it display some random deals
        $noresult = $html->find('h3[class*=text--b]', 0);
        if ($noresult != null && strpos($noresult->plaintext, $this->i8n('no-results')) !== false) {
            $this->items = [];
        } else {
            foreach ($list as $deal) {
                $item = [];
                $item['uri'] = $this->getDealURI($deal);
                $item['title'] = $this->getTitle($deal);
                $item['author'] = $deal->find('span.thread-username', 0)->plaintext;

                // Get the JSON Data stored as vue
                $jsonDealData = $this->getDealJsonData($deal);

                $item['content'] = '<table><tr><td><a href="'
                    . $item['uri']
                    . '"><img src="'
                    . $this->getImage($deal)
                    . '"/></td><td>'
                    . $this->getHTMLTitle($item)
                    . $this->getPrice($deal)
                    . $this->getDiscount($deal)
                    . $this->getShipsFrom($deal)
                    . $this->getShippingCost($deal)
                    . $this->getSource($jsonDealData)
                    . $deal->find('div[class*=' . $selectorDescription . ']', 0)->innertext
                    . '</td><td>'
                    . $this->getTemperature($jsonDealData)
                    . '</td></table>';

                // Check if a clock icon is displayed on the deal
                $clocks = $deal->find('svg[class*=icon--clock]');
                if ($clocks !== null && count($clocks) > 0) {
                    // Get the last clock, corresponding to the deal posting date
                    $clock = end($clocks);

                    // Find the text corresponding to the clock
                    $spanDateDiv = $clock->next_sibling();
                    $itemDate = $spanDateDiv->plaintext;
                    // In some case of a Local deal, there is no date, but we can use
                    // this case for other reason (like date not in the last field)
                    if ($this->contains($itemDate, $this->i8n('localdeal'))) {
                        $item['timestamp'] = time();
                    } elseif ($this->contains($itemDate, $this->i8n('relative-date-indicator'))) {
                        $item['timestamp'] = $this->relativeDateToTimestamp($itemDate);
                    } else {
                        $item['timestamp'] = $this->parseDate($itemDate);
                    }
                }
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
            returnClientError($this->i8n('thread-error'));
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
                // Count Links and Quote Links
                $content = str_get_html($item['content']);
                $countLinks = count($content->find('a[href]'));
                $countQuoteLinks = count($content->find('a[href][class=userHtml-quote-source]'));
                // Only add element if there are Links ans more links tant Quote links
                if ($countLinks > 0 && $countLinks > $countQuoteLinks) {
                    $this->items[] = $item;
                }
            } else {
                $this->items[] = $item;
            }
        }
    }

    /**
     * Extract the cookies obtained from the URL
     * @return array the array containing the cookies set by the URL
     */
    private function getCookiesHeaderValue($url)
    {
        $response = getContents($url, [], [], true);
        $setCookieHeaders = $response['headers']['set-cookie'] ?? [];
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
    private function getPrice($deal)
    {
        if (
            $deal->find(
                'span[class*=thread-price]',
                0
            ) != null
        ) {
            return '<div>' . $this->i8n('price') . ' : '
                . $deal->find(
                    'span[class*=thread-price]',
                    0
                )->plaintext
                . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Get the Title from a Deal if it exists
     * @return string String of the deal title
     */
    private function getTitle($deal)
    {
        $titleRoot = $deal->find('div[class*=threadGrid-title]', 0);
        $titleA = $titleRoot->find('a[class*=thread-link]', 0);
        $titleFirstChild = $titleRoot->first_child();
        if ($titleA !== null) {
            $title = $titleA->plaintext;
        } else {
            // In some case, expired deals have a different format
            $title = $titleRoot->find('span', 0)->plaintext;
        }

        return $title;
    }

    /**
     * Get the Title from a Talk if it exists
     * @return string String of the Talk title
     */
    private function getTalkTitle()
    {
        $html = getSimpleHTMLDOMCached($this->getInput('url'));
        $title = $html->find('.thread-title', 0)->plaintext;
        return $title;
    }

    /**
     * Get the HTML Title code from an item
     * @return string String of the deal title
     */
    private function getHTMLTitle($item)
    {
        if ($item['uri'] == '') {
            $html = '<h2>' . $item['title'] . '</h2>';
        } else {
            $html = '<h2><a href="' . $item['uri'] . '">'
                . $item['title'] . '</a></h2>';
        }

        return $html;
    }

    /**
     * Get the URI from a Deal if it exists
     * @return string String of the deal URI
     */
    private function getDealURI($deal)
    {
        $dealId = $deal->attr['id'];
        $uri = $this->i8n('bridge-uri') . $this->i8n('uri-deal') . str_replace('_', '-', $dealId);
        return $uri;
    }

    /**
     * Get the Shipping costs from a Deal if it exists
     * @return string String of the deal shipping Cost
     */
    private function getShippingCost($deal)
    {
        if ($deal->find('span[class*=space--ml-2 size--all-s overflow--wrap-off]', 0) != null) {
            if ($deal->find('span[class*=space--ml-2 size--all-s overflow--wrap-off]', 0)->children(1) != null) {
                return '<div>' . $this->i8n('shipping') . ' : '
                    . strip_tags($deal->find('span[class*=space--ml-2 size--all-s overflow--wrap-off]', 0)->children(1)->innertext)
                    . '</div>';
            } else {
                return '<div>' . $this->i8n('shipping') . ' : '
                    . strip_tags($deal->find('span[class*=text--color-greyShade flex--inline]', 0)->innertext)
                    . '</div>';
            }
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
        $data = Json::decode($deal->find('div[class=js-vue2]', 0)->getAttribute('data-vue2'));
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
    private function getDiscount($deal)
    {
        if ($deal->find('span[class*=mute--text text--lineThrough]', 0) != null) {
            $discountHtml = $deal->find('span[class=space--ml-1 size--all-l size--fromW3-xl]', 0);
            if ($discountHtml != null) {
                $discount = $discountHtml->plaintext;
            } else {
                $discount = '';
            }
            return '<div>' . $this->i8n('discount') . ' : <span style="text-decoration: line-through;">'
                . $deal->find(
                    'span[class*=mute--text text--lineThrough]',
                    0
                )->plaintext
                . '</span>&nbsp;'
                . $discount
                . '</div>';
        } else {
            return '';
        }
    }

    /**
     * Get the Picture URL from a Deal if it exists
     * @return string String of the deal Picture URL
     */
    private function getImage($deal)
    {
        $selectorLazy = implode(
            ' ', /* Notice this is a space! */
            [
                'thread-image',
                'width--all-auto',
                'height--all-auto',
                'imgFrame-img',
                'img--dummy',
                'js-lazy-img'
            ]
        );

        $selectorPlain = implode(
            ' ', /* Notice this is a space! */
            [
                'thread-image',
                'width--all-auto',
                'height--all-auto',
                'imgFrame-img',
            ]
        );
        if ($deal->find('img[class=' . $selectorLazy . ']', 0) != null) {
            return json_decode(
                html_entity_decode(
                    $deal->find('img[class=' . $selectorLazy . ']', 0)
                        ->getAttribute('data-lazy-img')
                )
            )->{'src'};
        } else {
            return $deal->find('img[class*=' . $selectorPlain . ']', 0)->src ?? '';
        }
    }

    /**
     * Get the originating country from a Deal if it exists
     * @return string String of the deal originating country
     */
    private function getShipsFrom($deal)
    {
        $selector = implode(
            ' ', /* Notice this is a space! */
            [
                'hide--toW2',
                'metaRibbon',
            ]
        );
        if ($deal->find('span[class*=' . $selector . ']', 0) != null) {
            $children = $deal->find('span[class*=' . $selector . ']', 0)->children(2);
            if ($children) {
                return '<div>' . $children->plaintext . '</div>';
            }
        }
        return '';
    }

    /**
     * Transforms a local date into a timestamp
     * @return int timestamp of the input date
     */
    private function parseDate($string)
    {
        $month_local = $this->i8n('local-months');
        $month_en = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        // A date can be prfixed with some words, we remove theme
        $string = $this->removeDatePrefixes($string);
        // We translate the local months name in the english one
        $date_str = trim(str_replace($month_local, $month_en, $string));

        // If the date does not contain any year, we add the current year
        if (!preg_match('/[0-9]{4}/', $string)) {
            $date_str .= ' ' . date('Y');
        }

        // Add the Hour and minutes
        $date_str .= ' 00:00';
        $date = DateTime::createFromFormat('j F Y H:i', $date_str);
        // In some case, the date is not recognized : as a workaround the actual date is taken
        if ($date === false) {
            $date = new DateTime();
        }
        return $date->getTimestamp();
    }

    /**
     * Remove the prefix of a date if it has one
     * @return the date without prefiux
     */
    private function removeDatePrefixes($string)
    {
        $string = str_replace($this->i8n('date-prefixes'), [], $string);
        return $string;
    }

    /**
     * Remove the suffix of a relative date if it has one
     * @return the relative date without suffixes
     */
    private function removeRelativeDateSuffixes($string)
    {
        if (count($this->i8n('relative-date-ignore-suffix')) > 0) {
            $string = preg_replace($this->i8n('relative-date-ignore-suffix'), '', $string);
        }
        return $string;
    }

    /**
     * Transforms a relative local date into a timestamp
     * @return int timestamp of the input date
     */
    private function relativeDateToTimestamp($str)
    {
        $date = new DateTime();

        // The minimal amount of time substracted is a minute : the seconds in the resulting date would be related to the execution time of the script.
        // This make no sense, so we set the seconds manually to "00".
        $date->setTime($date->format('H'), $date->format('i'), 0);

        // In case of update date, replace it by the regular relative date first word
        $str = str_replace($this->i8n('relative-date-alt-prefixes'), $this->i8n('local-time-relative')[0], $str);

        $str = $this->removeRelativeDateSuffixes($str);

        $search = $this->i8n('local-time-relative');

        $replace = [
            '-',
            'minute',
            'hour',
            'day',
            'month',
            'year',
            ''
        ];
        $date->modify(str_replace($search, $replace, $str));


        return $date->getTimestamp();
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
                return $this->i8n('bridge-name') . ' - ' . $this->i8n('title-group') . ' : ' . $this->getKey('group');
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
            . 'search/advanced?q='
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

        $url = $this->i8n('bridge-uri')
            . $this->i8n('uri-group') . $group . $order;
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
