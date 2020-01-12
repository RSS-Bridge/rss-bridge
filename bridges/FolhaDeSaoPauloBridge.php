<?php
class FolhaDeSaoPauloBridge extends FeedExpander {
	const MAINTAINER = 'somini';
	const NAME = 'Folha de São Paulo';
	const URI = 'https://www1.folha.uol.com.br';
	const DESCRIPTION = 'Returns the newest posts from Folha de São Paulo (full text)';
  const PARAMETERS = array(
    array(
      'feed' => array(
        'name' => 'feed',
        'type' => 'text',
        'title' => 'Select the sub-feed (see https://www1.folha.uol.com.br/feed/)',
        'exampleValue' => '/emcimadahora/rss091.xml',
      )
    )
  );

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);
    if($articleHTMLContent) {
      $text = $articleHTMLContent->find('div.c-news__body', 0)->innertext;
      $text = strip_tags($text, '<p><b><a><blockquote><img><em>');
      $item['content'] = $text;
    }
    else {
      Debug::log('???: ' . $item['uri']);
    }

		return $item;
	}

	public function collectData(){
    $feed_input = $this->getInput('feed');
    if (substr($feed_input, 0, strlen(self::URI)) === self::URI) {
      $feed_url = $feed_input;
    }
    else {
      $feed_url = self::URI . $this->getInput('feed');
    }
    Debug::log('URL: ' . $feed_url);
		$this->collectExpandableDatas($feed_url);
	}
}
