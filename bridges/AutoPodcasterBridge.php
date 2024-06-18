<?php

class AutoPodcasterBridge extends FeedExpander
{
    const MAINTAINER = 'boyska';
    const NAME = 'Auto Podcaster';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/pull/4016';
    const CACHE_TIMEOUT = 300; // 5m
    const DESCRIPTION = 'Make a "multimedia" podcast out of a normal feed';
    const PARAMETERS = [
        'url' => [
            'url' => [
                'name'          => 'URL',
                'exampleValue'  => 'https://lorem-rss.herokuapp.com/feed?unit=day',
                'required'      => true,
            ],
            'feed_only' => [
                'name'          => 'Only look at the content of the feed, don\'t check on the website',
                'type'          => 'checkbox',
                'defaultValue'  => 'checked',
                'required'      => false,
            ],
        ],
    ];

    public function collectData()
    {
        if (
            $this->getInput('url')
            && substr($this->getInput('url'), 0, strlen('http')) !== 'http'
        ) {
            // just in case someone find a way to access local files by playing with the url
            throw new \Exception('The url parameter must either refer to http or https protocol.');
        }
        $this->collectExpandableDatas($this->getURI());
    }

    protected function parseItem($item)
    {
        $dom = false;
        if (!$this->getInput('feed_only')) {
            $dom = getSimpleHTMLDOMCached($item['uri'], 86400 * 10); // 10d
            // $dom will be false in case of errors
        }
        $audios = [];
        if ($dom) {
            /* 1st extraction method: by "audio" tag */
            $audios = array_merge($audios, $this->extractAudio($dom));

            /* 2nd extraction method: by "iframe" tag */
            $audios = array_merge($audios, $this->extractIframeArchive($dom));
        } elseif ($item['content'] !== null) {
            $item_dom = str_get_html($item['content']);
            /* 1st extraction method: by "audio" tag */
            $audios = array_merge($audios, $this->extractAudio($item_dom));

            /* 2nd extraction method: by "iframe" tag */
            $audios = array_merge($audios, $this->extractIframeArchive($item_dom));
        }

        if ($audios === []) {
            return null;
        }

        // This will actually overwrite any exiting enclosures
        $item['enclosures'] = [];

        foreach ($audios as $audio) {
            $item['enclosures'][] = $audio['sources'][0];
        }

        return $item;
    }

    private function extractAudio($dom)
    {
        $audios = [];
        foreach ($dom->find('audio') as $audioEl) {
            $sources = [];
            if ($audioEl->src !== false) {
                $sources[] = $audioEl->src;
            }
            foreach ($audioEl->find('source') as $sourceEl) {
                $sources[] = $sourceEl->src;
            }
            if ($sources) {
                $audios[$sources[0]] = ['sources' => $sources];
            }
        }
        return $audios;
    }

    /**
     * Detects iframes pointing to https://archive.org/embed
     */
    private function extractIframeArchive($dom)
    {
        $audios = [];

        foreach ($dom->find('iframe') as $iframeEl) {
            if (strpos($iframeEl->src, 'https://archive.org/embed/') === 0) {
                $listURL = preg_replace('/\/embed\//', '/details/', $iframeEl->src, 1) . '?output=json';
                $baseURL = preg_replace('/\/embed\//', '/download/', $iframeEl->src, 1);

                $json = getContents($listURL);

                $list = Json::decode($json, false);
                $audios = [];
                foreach ($list->files as $name => $data) {
                    if (
                        $data->source === 'original'
                        && $this->isAudioFormat($data->format)
                    ) {
                        $audios[$baseURL . $name] = ['sources' => [$baseURL . $name]];
                    }
                }
                foreach ($list->files as $name => $data) {
                    if (
                        $data->source === 'derivative'
                        && $this->isAudioFormat($data->format)
                        && isset($audios[$baseURL . '/' . $data->original])
                    ) {
                        $audios[$baseURL . '/' . $data->original]['sources'][] = $baseURL . $name;
                    }
                }
            }
        }

        return $audios;
    }

    private function isAudioFormat($formatString): bool
    {
        // TODO: str_contains and str_starts_with
        return strpos($formatString, 'MP3') !== false || strpos($formatString, 'Ogg') === 0;
    }

    public function getName()
    {
        if (!is_null($this->getInput('url'))) {
            return self::NAME . ' : ' . $this->getInput('url');
        }

        return parent::getName();
    }

    public function getURI()
    {
        return $this->getInput('url') ?? parent::getURI();
    }
}

