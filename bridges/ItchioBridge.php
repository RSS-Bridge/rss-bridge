<?php

class ItchioBridge extends BridgeAbstract
{
    const NAME = 'itch.io';
    const URI = 'https://itch.io';
    const DESCRIPTION = 'Fetches the file uploads for a product';
    const MAINTAINER = 'jacquesh';
    const PARAMETERS = array(array(
        'url' => array(
            'name' => 'Product URL',
            'exampleValue' => 'https://remedybg.itch.io/remedybg',
            'required' => true,
        )
    ));
    const CACHE_TIMEOUT = 21600; // 6 hours

    public function collectData()
    {
        $url = $this->getInput('url');
        $html = getSimpleHTMLDOM($url);

        $title = $html->find('.game_title', 0)->innertext;

        $content = 'The following files are available to download:<br/>';
        foreach ($html->find('div.upload') as $element) {
            $filename = $element->find('strong.name', 0)->innertext;
            $filesize = $element->find('span.file_size', 0)->first_child()->innertext;
            $content = $content . $filename . ' (' . $filesize . ')<br/>';
        }

        // On 2021-04-28/29, itch.io changed their project page format so that the
        // 'last updated' timestamp is only shown to logged-in users.
        // Since we can't use the last-updated date to identify a post, we include
        // the description text in the input for the UID hash so that if the
        // project posts an update that changes the description but does not add
        // or rename any files, we'll still flag it as an update.
        $project_description = $html->find('div.formatted_description', 0)->plaintext;
        $uidContent = $project_description . $content;

        $item = array();
        $item['uri'] = $url;
        $item['uid'] = $uidContent;
        $item['title'] = 'Update for ' . $title;
        $item['content'] = $content;
        $this->items[] = $item;
    }
}
