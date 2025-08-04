<?php

class YouTubeFeedExpanderBridge extends FeedExpander
{
    const NAME = 'YouTube Feed Expander';
    const MAINTAINER = 'phantop';
    const URI = 'https://www.youtube.com/';
    const DESCRIPTION = 'Returns the latest videos from a YouTube channel';
    const PARAMETERS = [[
        'channel' => [
            'name' => 'Channel ID',
            'required' => true,
            // Example: vinesauce
            'exampleValue' => 'UCzORJV8l3FWY4cFO8ot-F2w',
        ],
        'embed' => [
            'name' => 'Add embed to entry',
            'type' => 'checkbox',
            'required' => false,
            'title' => 'Add embed to entry',
            'defaultValue' => 'checked',
        ],
        'embedurl' => [
            'name' => 'Use embed page as entry url',
            'type' => 'checkbox',
            'required' => false,
            'title' => 'Use embed page as entry url',
        ],
        'nocookie' => [
            'name' => 'Use nocookie embed page',
            'type' => 'checkbox',
            'required' => false,
            'title' => 'Use nocookie embed page'
        ],
    ]];

    public function getIcon()
    {
        if ($this->getInput('channel') != null) {
            $html = getSimpleHTMLDOMCached($this->getURI());
            return $html->find('[itemprop="thumbnailUrl"]', 0)->href;
        }
        return parent::getIcon();
    }

    public function collectData()
    {
        $url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $this->getInput('channel');
        $this->collectExpandableDatas($url);
    }

    protected function parseItem(array $item)
    {
        $id = $item['yt']['videoId'];
        $item['comments'] = $item['uri'] . '#comments';
        $item['uid'] = $item['id'];

        $thumbnail = sprintf('https://img.youtube.com/vi/%s/maxresdefault.jpg', $id);
        $item['enclosures'] = [$thumbnail];

        $item['content'] = $item['media']['group']['description'];
        $item['content'] = str_replace("\n", '<br>', $item['content']);
        unset($item['media']);

        $embedURI = self::URI;
        if ($this->getInput('nocookie')) {
            $embedURI = 'https://www.youtube-nocookie.com/';
        }
        $embed = $embedURI . 'embed/' . $id;
        if ($this->getInput('embed')) {
            $iframe_fmt = '<iframe width="448" height="350" src="%s" title="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'; //phpcs:ignore
            $iframe = sprintf($iframe_fmt, $embed, $item['title']) . '<br>';
            $item['content'] = $iframe . $item['content'];
        }
        if ($this->getInput('embedurl')) {
            $item['uri'] = $embed;
        }

        return $item;
    }
}
