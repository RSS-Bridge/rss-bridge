<?php
class GithubTagsBridge extends BridgeAbstract {

	const MAINTAINER = 'floviolleau';
	const NAME = 'Github Tags';
	const URI = 'https://api.github.com/repos/';
	const CACHE_TIMEOUT = 7200;
	const DESCRIPTION = 'Returns the tags of a github project';

	const PARAMETERS = array(array(
		'u' => array(
			'name' => 'User name',
			'required' => true
		),
		'p' => array(
			'name' => 'Project name',
			'required' => true
		),
		'show_alpha_beta' => array(
			'name' => 'Show alpha/beta',
			'type' => 'checkbox'
		),
		'filter_alpha_beta' => array(
			'name' => 'Filter alpha/beta',
			'defaultValue' => 'alpha|beta'
		),
		'show_rc' => array(
			'name' => 'Show release candidate',
			'type' => 'checkbox'
		),
		'filter_rc' => array(
			'name' => 'Filter release candidate',
			'defaultValue' => 'RC'
		),
		'item_limit' => array(
			'name' => 'Limit number of returned items',
			'type' => 'number',
			'defaultValue' => 20
		)
	));

	public function getURI(){
		if(null !== $this->getInput('u') && null !== $this->getInput('p')) {
			$uri = static::URI . $this->getInput('u') . '/'
				. $this->getInput('p') . '/tags';

			return $uri;
		}

		return parent::getURI();
	}

	public function collectData(){
		$url = $this->getURI();

		$limit = $this->getInput('item_limit');

		if ($limit < 1) {
			$limit = 20;
		}

		$header = array(
			'Content-Type: application/json',
		);

		$content = getContents($url, $header)
			or returnServerError('Could not request Github api . Tried: ' . $url);

		$json = json_decode($content);

		if(sizeof($json) === 0) {
			return;
		}

		$today = date('d/m/Y');
		foreach($json as $tag) {
			if(!$this->getInput('show_alpha_beta')) {
				$regex = '/' . $this->getInput('filter_alpha_beta') . '/';
				if(preg_match($regex, $tag->name, $matches) > 0) {
					continue;
				}
			}
			if(!$this->getInput('show_rc')) {
				$regex = '/' . $this->getInput('filter_rc') . '/';
				if(preg_match($regex, $tag->name, $matches) > 0) {
					continue;
				}
			}

			$item['uri'] = $tag->zipball_url;
			$item['title'] = $tag->name;
			$item['author'] = $this->getInput('u');
			$item['content'] = '';
			$item['date'] = $today;
			$item['uid'] = $tag->node_id;

			$this->items[] = $item;

			if (count($this->items) >= $limit) {
				break;
			}
		}
	}
}
