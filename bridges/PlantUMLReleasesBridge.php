<?php

/**
 * PlantUML releases bridge showing latest releases content
 * @author nicolas-delsaux
 *
 */
class PlantUMLReleasesBridge extends BridgeAbstract
{
    const MAINTAINER = 'Riduidel';
    const NAME = 'PlantUML Releases';
    const AUTHOR = 'PlantUML team';
    const URI = 'https://plantuml.com/changes';

    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'PlantUML releases bridge, showing for each release the changelog';
    const ITEM_LIMIT = 10;

    public function getURI()
    {
        return self::URI;
    }

    public function collectData()
    {
        $html = defaultLinkTo(getSimpleHTMLDOM($this->getURI()), self::URI);

        $num_items = 0;
        $main = $html->find('div[id=root]', 0);
        foreach ($main->find('h2') as $release) {
            // Limit to $ITEM_LIMIT number of results
            if ($num_items++ >= self::ITEM_LIMIT) {
                break;
            }
            $item = array();
            $item['author'] = self::AUTHOR;
            $release_text = $release->innertext;
            if (preg_match('/(.+) \((.*)\)/', $release_text, $matches)) {
                $item['title'] = $matches[1];
                $item['timestamp'] = preg_replace('/(\d+) (\w{3})\w*, (\d+)/', '${1} ${2} ${3}', $matches[2]);
            } else {
                $item['title'] = $release_text;
            }
            $item['uri'] = $this->getURI();
            $item['content'] = $release->next_sibling();
            $this->items[] = $item;
        }
    }
}
