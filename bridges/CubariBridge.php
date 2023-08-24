<?php

class CubariBridge extends BridgeAbstract
{
    const NAME = 'Cubari';
    const URI = 'https://cubari.moe';
    const DESCRIPTION = 'Parses given cubari-formatted JSON file for updates.';
    const MAINTAINER = 'KamaleiZestri';
    const PARAMETERS = [[
        'gist' => [
            'name' => 'Gist/Raw Url',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'https://raw.githubusercontent.com/kurisumx/baka/main/ikedan'
        ]
    ]];

    private $mangaTitle = '';

    public function getName()
    {
        if (!empty($this->mangaTitle)) {
            return $this->mangaTitle . ' - ' . self::NAME;
        } else {
            return self::NAME;
        }
    }

    public function getURI()
    {
        if ($this->getInput('gist') != '') {
            return self::URI . '/read/gist/' . $this->getEncodedGist();
        } else {
            return self::URI;
        }
    }

    /**
     * The Cubari bridge.
     *
     * Cubari urls are base64 encodes of a given github raw or gist link described as below:
     * https://cubari.moe/read/gist/${bаse64.url_encode(raw/<rest of the url...>)}/
     * https://cubari.moe/read/gist/${bаse64.url_encode(gist/<rest of the url...>)}/
     * https://cubari.moe/read/gist/${gitio shortcode}
     *
     * This bridge uses just the raw/gist and generates matching cubari urls.
     */
    public function collectData()
    {
        $jsonSite = getContents($this->getInput('gist'));
        $jsonFile = json_decode($jsonSite, true);

        $this->mangaTitle = $jsonFile['title'];

        $chapters = $jsonFile['chapters'];

        foreach ($chapters as $chapnum => $chapter) {
            $item = $this->getItemFromChapter($chapnum, $chapter);
            $this->items[] = $item;
        }

        array_multisort(array_column($this->items, 'timestamp'), SORT_DESC, $this->items);
    }

    protected function getEncodedGist()
    {
        $url = $this->getInput('gist');

        preg_match('/\/([a-z]*)\.githubusercontent.com(.*)/', $url, $matches);

        // raw or gist is first match.
        $unencoded = $matches[1] . $matches[2];

        return base64_encode($unencoded);
    }

    private function getSanitizedHash($string)
    {
        return hash('sha1', preg_replace('/[^a-zA-Z0-9\-\.]/', '', ucwords(strtolower($string))));
    }

    protected function getItemFromChapter($chapnum, $chapter)
    {
        $item = [];

        $item['uri'] = $this->getURI() . '/' . $chapnum;
        $item['title'] = 'Chapter ' . $chapnum . ' - ' . $chapter['title'] . ' - ' . $this->mangaTitle;
        foreach ($chapter['groups'] as $key => $value) {
            $item['author'] = $key;
        }
        $item['timestamp'] = $chapter['last_updated'];

        $item['content'] = '<p>Manga: <a href=' . $this->getURI() . '>' . $this->mangaTitle . '</a> </p>
			<p>Chapter Number: ' . $chapnum . '</p>
			<p>Chapter Title: <a href=' . $item['uri'] . '>' . $chapter['title'] . '</a></p>
			<p>Group: ' . $item['author'] . '</p>';

        $item['uid'] = $this->getSanitizedHash($item['title'] . $item['author']);

        return $item;
    }
}
