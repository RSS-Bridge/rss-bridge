<?php

class HelloAssoBridge extends BridgeAbstract
{
    const NAME = 'HelloAsso';
    const URI = 'https://www.helloasso.com';
    const DESCRIPTION = 'Generates feed of fundraising forms listed on a given HelloAsso organization page';
    const MAINTAINER = 'No maintainer';

    const PARAMETERS = [[
        'slug' => [
            'name' => 'Organization slug',
            'type' => 'text',
            'required' => true,
            'title' => 'Insert Organization short name (found in the URL)',
            'exampleValue' => 'ligue-contre-le-cancer-comite-essonne'
        ]
    ]];

    private $orgname;

    public function getURI()
    {
        $slug = $this->getInput('slug') ?: '';
        return static::URI . '/associations/' . $slug;
    }

    public function getName()
    {
        return $this->orgname ? $this->orgname . ' - ' . static::NAME : static::NAME;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = defaultLinkTo($html, $this->getURI());

        $orgname = $html->find('[class~="Intro--Name"]', 0)
        or returnServerError('Could not find org name!');
        $this->orgname = $orgname->plaintext;

        foreach ($html->find('ul[class~="ActionList"] li') as $element) {
            $uri = $element->find('.ActionWrapper a', 0)
            or returnServerError('Could not find action uri!');

            $title = $element->find('.ActionContent--Text h3', 0);

            $date = $element->find('[class~="Number-Date"]', 0);
            $address = $element->find('[class~="Data-AddressName"]', 0);

            $item = [];
            $item['uri'] = $uri->href;
            $item['title'] = $title->plaintext ?? '';
            $item['content'] = ($date->plaintext ?? '')
                . ('<br>' . $address->plaintext ?? '');

            $this->items[] = $item;
        }
    }
}
