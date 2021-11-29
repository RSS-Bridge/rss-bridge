<?php

class DaveRamseyBlogBridge extends BridgeAbstract {
	const MAINTAINER = 'johnpc';
	const NAME = 'Dave Ramsey Blog';
	const URI = 'https://www.daveramsey.com/blog';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Returns blog posts from daveramsey.com';

	public function collectData()
	{
		$this->items[] = array(
			'uri'		=> 'https://www.ramseysolutions.com/articles',
			'title' 	=> self::NAME,
			'content'	=> <<<'CONTENT'
The blog https://www.daveramsey.com/blog is retired. <br><br>
See https://www.ramseysolutions.com/articles
CONTENT
		);
	}
}
