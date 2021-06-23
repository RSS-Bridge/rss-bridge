<?php

class CurrentTimeBridge extends XPathAbstract {

	const NAME = 'Current Time';
	const URI = 'https://www.timeanddate.com/worldclock/usa/new-york';
	const DESCRIPTION = 'Shows you the current time in New York. Perfect for Testing';
	const MAINTAINER = 'dhuschde';
	const CACHE_TIMEOUT = 10; //10 seconds

	const XPATH_EXPRESSION_ITEM = '.// *[@id="qlook"]';
	const XPATH_EXPRESSION_ITEM_TITLE = '/html/body/div[6]/header/div[2]/div/section[1]/div/h1';
	const XPATH_EXPRESSION_ITEM_CONTENT = '.// *[@id="ct"]';
	const XPATH_EXPRESSION_ITEM_URI = '.// *[@id="full-clk"]/@href';
	const XPATH_EXPRESSION_ITEM_AUTHOR = '/html/body/div[6]/main/article/section[1]/div[2]/table/tbody/tr[2]/td/a';
	const XPATH_EXPRESSION_ITEM_TIMESTAMP = '.// *[@id="ctdat"]';
	const XPATH_EXPRESSION_ITEM_ENCLOSURES = '';
	const XPATH_EXPRESSION_ITEM_CATEGORIES = '';
	const SETTING_FIX_ENCODING = false;

	/**
	 * Source Web page URL (should provide either HTML or XML content)
	 * @return string
	 */

        protected function getSourceUrl(){
                return 'https://www.timeanddate.com/worldclock/usa/new-york';
        }

        public function getIcon() {
                $feedicon = 'https://www.google.com/s2/favicons?domain=timeanddate.com/';
                return $feedicon;
        }

}

