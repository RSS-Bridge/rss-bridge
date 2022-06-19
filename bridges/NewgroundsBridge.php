<?php

class NewgroundsBridge extends BridgeAbstract
{
	const NAME = 'Newgrounds';
	const URI = 'https://www.newgrounds.com';
	const DESCRIPTION = 'Get the latest art from a given user';
	const MAINTAINER = 'KamaleiZestri';
	const PARAMETERS = array(array(
		'username' => array(
			'name' => 'Username',
			'type' => 'text',
			'required' => true,
			'exampleValue' => 'TomFulp'
		)
	));

	public function getName()
	{
		if (!empty($this->getInput('username'))) {
			return $this->getInput('username') . ' - ' . self::NAME;
		}
		return parent::getName();
	}

	public function getURI()
	{
		if (!empty($this->getInput('username'))) {
			return 'https://' . $this->getInput('username') . '.newgrounds.com/art';
		}
		return parent::getURI();
	}

	public function collectData()
	{
		$userlink = 'https://' . $this->getInput('username') . '.newgrounds.com/art';
		$html = getSimpleHTMLDOM($userlink);

		$posts = $html->find('.item-portalitem-art-medium');

		foreach ($posts as $post) {
			$item = array();

			$item['author'] = $this->getInput('username');
			$item['uri'] = $post->href;
			$item['uid'] = $item['uri'];

			$titleOrRestricted = $post->find('h4')[0]->innertext;

			//Newgrounds doesn't show public previews for NSFW content.
			if ($titleOrRestricted == 'Restricted Content: Sign in to view!') {
				$item['title'] = 'NSFW: ' . $item['uri'];
				$item['content'] = <<<EOD
<a href="{$item['uri']}">
{$item['title']}
</a>
EOD;
			} else {
				$item['title'] = $titleOrRestricted;
				$item['content'] = <<<EOD
<a href="{$item['uri']}">
<img
    style="align:top; width:270px; border:1px solid black;"
    alt="{$item['title']}"
    src="{$post->find('img')[0]->src}"
    title="{$item['title']}" />
</a>
EOD;
			}

			$this->items[] = $item;
		}
	}
}
