<?php
class WordPressPluginUpdateBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'WordPress Plugins Update Bridge';
	const URI = 'https://wordpress.org/plugins/';
	const CACHE_TIMEOUT = 86400; // 24h = 86400s
	const DESCRIPTION = 'Returns latest updates of WordPress.com plugins.';

	const PARAMETERS = array(
		array(
			'pluginUrl' => array(
				'name' => 'URL to the plugin',
				'required' => true
			)
		)
	);

	public function collectData(){

		$request = str_replace('/', '', $this->getInput('pluginUrl'));
		$page = self::URI . $request . '/changelog/';

		$html = getSimpleHTMLDOM($page);

		$content = $html->find('.block-content', 0);

		$item = array();
		$item['content'] = '';
		$version = null;

		foreach($content->children() as $element) {

			if($element->tag != 'h4') {

				$item['content'] .= $element;

			} else {

				if($version == null) {

					$version = $element;

				} else {

					$item['title'] = $version;
					$item['uri'] = 'https://downloads.wordpress.org/plugin/' . $request . '.' . strip_tags($version) . '.zip';
					$this->items[] = $item;

					$version = $element;
					$item = array();
					$item['content'] = '';

				}

			}

		}

		$item['uri'] = 'https://downloads.wordpress.org/plugin/' . $request . '.' . strip_tags($version) . '.zip';
		$item['title'] = $version;
		$this->items[] = $item;

	}

	public function getName(){
		if(!is_null($this->getInput('q'))) {
			return $this->getInput('q') . ' : ' . self::NAME;
		}

		return parent::getName();
	}
}
