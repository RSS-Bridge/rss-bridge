<?php

class CeskaTelevizeBridge extends BridgeAbstract
{
    const NAME = 'Česká televize Bridge';
    const URI = 'https://www.ceskatelevize.cz';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Return newest videos';
    const MAINTAINER = 'kolarcz';

    const PARAMETERS = [
        [
            'url' => [
                'name' => 'url to the show',
                'required' => true,
                'exampleValue' => 'https://www.ceskatelevize.cz/porady/1097181328-udalosti/'
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('url');

        $validUrl = '/^(https:\/\/www\.ceskatelevize\.cz\/porady\/\d+-[a-z0-9-]+\/)(bonus\/)?$/';
        if (!preg_match($validUrl, $url, $match)) {
            returnServerError('Invalid url');
        }

        $category = $match[4] ?? 'nove';
        $fixedUrl = "{$match[1]}dily/{$category}/";

        $html = getSimpleHTMLDOM($fixedUrl);

        $this->feedUri = $fixedUrl;
        $this->feedName = str_replace('Přehled dílů — ', '', $this->fixChars($html->find('title', 0)->plaintext));
        if ($category !== 'nove') {
            $this->feedName .= " ({$category})";
        }

        foreach ($html->find('#episodeListSection a[data-testid=card]') as $element) {
            $itemContent = $element->find('p[class^=content-]', 0);
            $itemDate = $element->find('div[class^=playTime-] span, [data-testid=episode-item-broadcast] span', 0);

            // Remove special characters and whitespace
            $cleanDate = preg_replace('/[^0-9.]/', '', $itemDate->plaintext);

            $item = [
                'title'     => $this->fixChars($element->find('h3', 0)->plaintext),
                'uri'       => self::URI . $element->getAttribute('href'),
                'content'   => '<img src="' . $element->find('img', 0)->getAttribute('srcset') . '" /><br />' . $this->fixChars($itemContent->plaintext),
                'timestamp' => $this->getUploadTimeFromString($cleanDate),
            ];

            $this->items[] = $item;
        }
    }

    private function getUploadTimeFromString($string)
    {
        if (strpos($string, 'dnes') !== false) {
            return strtotime('today');
        } elseif (strpos($string, 'včera') !== false) {
            return strtotime('yesterday');
        } elseif (!preg_match('/(\d+).(\d+).((\d+))?/', $string, $match)) {
            returnServerError('Could not get date from Česká televize string');
        }

        $date = sprintf('%04d-%02d-%02d', $match[3] ?? date('Y'), $match[2], $match[1]);
        return strtotime($date);
    }

    private function fixChars($text)
    {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    public function getURI()
    {
        return $this->feedUri ?? parent::getURI();
    }

    public function getName()
    {
        return $this->feedName ?? parent::getName();
    }
}
