<?php

class WallpaperflareBridge extends XPathAbstract {

	const NAME = 'Wallpaperflare';
	const URI = 'https://wallpaperflare.com';
	const DESCRIPTION = 'Wallpapers from Wallpaperflare';
	const MAINTAINER = 'dhuschde';
	const PARAMETERS = array(
		'' => array(
			'search' => array(
				'name' => 'Search',
	                        'required' => true
                )
        ));


	const CACHE_TIMEOUT = 3600;

	const XPATH_EXPRESSION_ITEM = './/figure';
	const XPATH_EXPRESSION_ITEM_TITLE = './/img/@title';
	const XPATH_EXPRESSION_ITEM_CONTENT = '';
	const XPATH_EXPRESSION_ITEM_URI = './/a[@itemprop="url"]/@href';
	const XPATH_EXPRESSION_ITEM_AUTHOR = '/html[1]/body[1]/main[1]/section[1]/h1[1]';
	const XPATH_EXPRESSION_ITEM_TIMESTAMP = 'N/A';
	const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img[@class="lazy"]/@data-src';
	const XPATH_EXPRESSION_ITEM_CATEGORIES = './/figcaption[@itemprop="caption description"]';
	const SETTING_FIX_ENCODING = true;

	/**
	 * Source Web page URL (should provide either HTML or XML content)
	 * @return string
	 */
	protected function getSourceUrl(){

		$search = $this->getInput('search');
		return 'https://www.wallpaperflare.com/search?wallpaper=' . $search;
	}
        public function getIcon() {
                $feedicon = 'https://www.google.com/s2/favicons?domain=https://www.wallpaperflare.com/';
                return $feedicon;
        }


        public function getName() {
			$search = $this->getInput('search');
                        return 'Wallpaperflare - ' . $search;
                }

}
