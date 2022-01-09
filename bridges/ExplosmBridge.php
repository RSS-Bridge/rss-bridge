<?php
class ExplosmBridge extends BridgeAbstract {

	const MAINTAINER = 'bockiii';
	const NAME = 'Explosm Bridge';
	const URI = 'https://www.explosm.net/';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'Returns the last 5 comics';
	const PARAMETERS = array(
		'Get latest posts' => array(
			'limit' => array(
				'name' => 'Posts limit',
				'type' => 'number',
				'title' => 'Maximum number of items to return',
				'defaultValue' => 5
				)
			)
		);

	public function collectData(){
		$limit = $this->getInput('limit');
		$latest = getSimpleHTMLDOM('https://explosm.net/comics/latest');
		$image = $latest->find('div[id=comic]', 0)->find('img', 0)->getAttribute('src');
		$date_string = $latest->find('p[class*=Author__P]', 0)->innertext;
		$next_data_string = $latest->find('script[id=__NEXT_DATA__]', 0)->innertext;
		$exp = '/{\\\"latest\\\":\[{\\\"slug\\\":\\\"(.*?)\\ /';
		$reg_array = array();
		preg_match($exp, $next_data_string, $reg_array);
		$comic_id = $reg_array[1];
		$comic_id = substr($comic_id, 0, strpos($comic_id, '\\'));
		$item = array();
		$item['uri'] = $this::URI . 'comics/' . $comic_id;
		$item['uid'] = $this::URI . 'comics/' . $comic_id;
		$item['title'] = 'Comic for ' . $date_string;
		$item['timestamp'] = strtotime($date_string);
		$item['author'] = $latest->find('p[class*=Author__P]', 2)->innertext;
		$item['content'] = '<img src="' . $image . '" />';
		$this->items[] = $item;

		$next_comic = substr($this::URI, 0, -1)
			. $latest->find('div[class*=MainComic__Selector]', 0)->find('a', 0)->getAttribute('href');
		// use index 1 as the latest comic was already found
		for ($i = 1; $i <= $limit; $i++) {
			$this_comic = getSimpleHTMLDOM($next_comic);
			$image = $this_comic->find('div[id=comic]', 0)->find('img', 0)->getAttribute('src');
			$date_string = $this_comic->find('p[class*=Author__P]', 0)->innertext;
			$item = array();
			$item['uri'] = $next_comic;
			$item['uid'] = $next_comic;
			$item['title'] = 'Comic for ' . $date_string;
			$item['timestamp'] = strtotime($date_string);
			$item['author'] = $this_comic->find('p[class*=Author__P]', 2)->innertext;
			$item['content'] = '<img src="' . $image . '" />';
			$this->items[] = $item;
			$next_comic = substr($this::URI, 0, -1)
				. $this_comic->find('div[class*=MainComic__Selector]', 0)->find('a', 0)->getAttribute('href'); // get next comic link
		}
	}
}
