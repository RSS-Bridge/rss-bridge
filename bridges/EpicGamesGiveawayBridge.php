<?php
class EpicGamesGiveawayBridge extends BridgeAbstract
{
	const NAME = 'Epic Games Store giveaways';
	const URI = 'https://ep.reddit.com/r/FreeGameFindings/new';
	const DESCRIPTION = 'Latest free games from the Epic Game Store';
	const MAINTAINER = 't0stiman';

	public function collectData()
	{
		$page = getSimpleHTMLDOMCached(self::URI)
			or returnServerError('could not retrieve page');

		$this->items[] = array();

		//for each post
		foreach($page->find('div.link a.title') as $post) {
			$item = array();

			$uri = $post->getAttribute('href');
			//filter out everything that doesn't link to epic games
			if($uri == '' || !stripos($uri, 'epicgames.com')) {
				continue;
			}
			$title = $post->innertext;
			//only games pls
			if(!stripos($title, '(game)')) {
				continue;
			}

			$item['uri'] = $uri;
			$item['title'] = $title;
			$item['content'] = '<p><a href="' . $uri . '"> Go to giveaway.</a></p>';
			$item['author'] = 'Epic Games Store';

			//add the item to the list
			array_push($this->items, $item);
		}
	}
}
