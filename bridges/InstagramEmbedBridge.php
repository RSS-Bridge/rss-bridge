<?php

class InstagramEmbedBridge extends BridgeAbstract
{
    const MAINTAINER = 'sysadminstory';
    const NAME = 'Instagram Embed Bridge';
    const URI = 'https://www.instagram.com/';
    const DESCRIPTION = 'Returns the newest Instagram from a specific usernane using the Instagram Embed page';

    const PARAMETERS = [
        'Username' => [
            'u' => [
                'name' => 'username',
                'exampleValue' => 'aesoprockwins',
                'required' => true
            ]
        ],
        'global' => [
            'media_type' => [
                'name' => 'Media type',
                'type' => 'list',
                'required' => false,
                'values' => [
                    'All' => 'all',
                    'Video' => 'video',
                    'Picture' => 'picture',
                    'Multiple' => 'multiple',
                ],
                'defaultValue' => 'all'
            ],
            'direct_links' => [
                'name' => 'Use direct media links',
                'type' => 'checkbox',
            ]
        ]

    ];

    const TEST_DETECT_PARAMETERS = [
        'https://www.instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
        'https://instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
        'http://www.instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
    ];

    public function collectData()
    {
        $username = $this->getInput('u');
        $directLink = !is_null($this->getInput('direct_links')) && $this->getInput('direct_links');

        // Get the HTML code of the profile embed page, and extract the JSON of it
        $html = getSimpleHTMLDOMCached(self::URI . $username . '/embed/');
        $jsCode = $html->find('body', 0)->find('script', 3)->innertext;
        $regex = '#"contextJSON":"(.*)"}\]\],\["NavigationMetrics"#m';
        preg_match($regex, $jsCode, $matches);
        $jsVariable = $matches[1];
        $jsonString = stripcslashes($jsVariable);
        $jsonData = Json::decode($jsonString, false);
        $medias = $jsonData->context->graphql_media;

        foreach ($medias as $graphqlMedia) {
            $media = $graphqlMedia->shortcode_media;
            switch ($this->getInput('media_type')) {
                case 'all':
                    break;
                case 'video':
                    if ($media->__typename != 'GraphVideo' || !$media->is_video) {
                        continue 2;
                    }
                    break;
                case 'picture':
                    if ($media->__typename != 'GraphImage') {
                        continue 2;
                    }
                    break;
                case 'multiple':
                    if ($media->__typename != 'GraphSidecar') {
                        continue 2;
                    }
                    break;
                default:
                    break;
            }

            $item = [];
            $item['uri'] = self::URI . 'p/' . $media->shortcode . '/';

            if (isset($media->owner->username)) {
                $item['author'] = $media->owner->username;
            }

            $textContent = $this->getTextContent($media);

            $item['title'] = ($media->is_video ? '▶ ' : '') . $textContent;
            $titleLinePos = strpos(wordwrap($item['title'], 120), "\n");
            if ($titleLinePos != false) {
                $item['title'] = substr($item['title'], 0, $titleLinePos) . '...';
            }

            if ($directLink) {
                $mediaURI = $media->display_url;
            } else {
                $mediaURI = self::URI . 'p/' . $media->shortcode . '/media?size=l';
            }

            $pattern = ['/\@([\w\.]+)/', '/#([\w\.]+)/'];
            $replace = [
                '<a href="https://www.instagram.com/$1">@$1</a>',
                '<a href="https://www.instagram.com/explore/tags/$1">#$1</a>'];

            switch ($media->__typename) {
                case 'GraphSidecar':
                    $data = $this->getInstagramSidecarData($item['uri'], $item['title'], $media, $textContent);
                    $item['content'] = $data[0];
                    $item['enclosures'] = $data[1];
                    break;
                case 'GraphImage':
                    $item['content'] = '<a href="' . htmlentities($item['uri']) . '" target="_blank">';
                    $item['content'] .= '<img src="' . htmlentities($mediaURI) . '" alt="' . $item['title'] . '" />';
                    $item['content'] .= '</a><br><br>' . nl2br(preg_replace($pattern, $replace, htmlentities($textContent)));
                    $item['enclosures'] = [$mediaURI];
                    break;
                case 'GraphVideo':
                    $data = $this->getInstagramVideoData($item['uri'], $mediaURI, $media, $textContent);
                    $item['content'] = $data[0];
                    if ($directLink) {
                        $item['enclosures'] = $data[1];
                    } else {
                        $item['enclosures'] = [$mediaURI];
                    }
                    $item['thumbnail'] = $mediaURI;
                    break;
                default:
                    break;
            }
            $item['timestamp'] = $media->taken_at_timestamp;

            $this->items[] = $item;
        }
    }


    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return $this->getInput('u') . ' - Instagram Embed Bridge';
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return self::URI . urlencode($this->getInput('u')) . '/';
        }
        return parent::getURI();
    }

    protected function getTextContent($media)
    {
        $textContent = '(no text)';
        //Process the first element, that isn't in the node graph
        if (count($media->edge_media_to_caption->edges) > 0) {
            $textContent = trim($media->edge_media_to_caption->edges[0]->node->text);
        }
        return $textContent;
    }

    // returns Video post's contents and enclosures
    protected function getInstagramVideoData($uri, $mediaURI, $mediaInfo, $textContent)
    {
        $content = '<video controls>';
        $content .= '<source src="' . $mediaInfo->video_url . '" poster="' . $mediaURI . '" type="video/mp4">';
        $content .= '<img src="' . $mediaURI . '" alt="">';
        $content .= '</video><br>';
        $content .= '<br>' . nl2br(htmlentities($textContent));

        return [$content, [$mediaInfo->video_url]];
    }

    // returns Sidecar(a post which has multiple media)'s contents and enclosures
    protected function getInstagramSidecarData($uri, $postTitle, $mediaInfo, $textContent)
    {
        $enclosures = [];
        $content = '';
        foreach ($mediaInfo->edge_sidecar_to_children->edges as $singleMedia) {
            $singleMedia = $singleMedia->node;
            if ($singleMedia->is_video) {
                if (in_array($singleMedia->video_url, $enclosures)) {
                    continue; // check if not added yet
                }
                $content .= '<video controls><source src="' . $singleMedia->video_url . '" type="video/mp4"></video><br>';
                array_push($enclosures, $singleMedia->video_url);
            } else {
                if (in_array($singleMedia->display_url, $enclosures)) {
                    continue; // check if not added yet
                }
                $content .= '<a href="' . $singleMedia->display_url . '" target="_blank">';
                $content .= '<img src="' . $singleMedia->display_url . '" alt="' . $postTitle . '" />';
                $content .= '</a><br>';
                array_push($enclosures, $singleMedia->display_url);
            }
        }
        $content .= '<br>' . nl2br(htmlentities($textContent));

        return [$content, $enclosures];
    }

    public function detectParameters($url)
    {
        $params = [];

        // By username
        $regex = '/^(https?:\/\/)?(www\.)?instagram\.com\/([^\/?\n]+)/';

        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'Username';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }
}
