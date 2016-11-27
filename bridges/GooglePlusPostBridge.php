<?php
class GooglePlusPostBridge extends BridgeAbstract
{
	protected $_title;
	protected $_url;

	const MAINTAINER = "Grummfy";
	const NAME = "Google Plus Post Bridge";
	const URI = "https://plus.google.com/";
	const CACHE_TIMEOUT = 600; //10min
	const DESCRIPTION = "Returns user public post (without API).";

    const PARAMETERS = array( array(
        'username'=>array(
            'name'=>'username or Id',
            'required'=>true
        )
    ));

	public function collectData()
	{
		// get content parsed
//		$html = getSimpleHTMLDOM(__DIR__ . '/../posts2.html'
		$html = getSimpleHTMLDOM(self::URI . urlencode($this->getInput('username')) . '/posts'
			// force language
			, false, stream_context_create(array('http'=> array(
			'header'    => 'Accept-Language: fr,fr-be,fr-fr;q=0.8,en;q=0.4,en-us;q=0.2;*' . "\r\n"
			)))
		) OR returnServerError('No results for this query.');

		// get title, url, ... there is a lot of intresting stuff in meta
		$this->_title = $html->find('meta[property]', 0)->getAttribute('content');
		$this->_url = $html->find('meta[itemprop=url]', 0)->getAttribute('content');

//		foreach ($html->find('meta') as $e)
//		{
//			$item = array();
//			$item['content'] = var_export($e->attr, true);
//			$this->items[] = $item;
//		}

		// div[jsmodel=XNmfOc]
		foreach($html->find('div.yt') as $post)
		{
			$item = array();
//			$item['content'] = $post->find('div.Al', 0)->innertext;
			$item['username'] = $item['fullname'] = $post->find('header.lea h3 a', 0)->innertext;
			$item['id'] = $post->getAttribute('id');
//			$item['title'] = $item['fullname'] = $post->find('header.lea', 0)->plaintext;
			$item['avatar'] = $post->find('div.ys img', 0)->src;
//			var_dump((($post->find('a.o-U-s', 0)->getAllAttributes())));
			$item['uri'] = self::URI . $post->find('a.o-U-s', 0)->href;
			$item['timestamp'] = strtotime($post->find('a.o-U-s', 0)->plaintext);
			$this->items[] = $item;

			// hashtag to treat : https://plus.google.com/explore/tag
			$hashtags = array();
			foreach($post->find('a.d-s') as $hashtag)
			{
				$hashtags[ trim($hashtag->plaintext) ] = self::URI . $hashtag->href;
			}

			$item['content'] = '';

			// avatar display
			$item['content'] .= '<div style="float:left; margin: 0 0.5em 0.5em 0;"><a href="' . self::URI . urlencode($this->getInput('username'));
			$item['content'] .= '"><img align="top" alt="avatar" src="' . $item['avatar'].'" />' . $item['username'] . '</a></div>';

			$content = $post->find('div.Al', 0);

			// alter link
//			$content = $content->innertext;
//			$content = str_replace('href="./', 'href="' . self::URI, $content);
//			$content = str_replace('href="photos', 'href="' . self::URI . 'photos', $content);
			// XXX ugly but I don't have any idea how to do a better stuff, str_replace on link doesn't work as expected and ask too many checks
			foreach($content->find('a') as $link)
			{
				$hasHttp = strpos($link->href, 'http');
				$hasDoubleSlash = strpos($link->href, '//');

				if ((!$hasHttp && !$hasDoubleSlash)
					|| (false !== $hasHttp && strpos($link->href, 'http') != 0)
					|| (false === $hasHttp && false !== $hasDoubleSlash && $hasDoubleSlash != 0))
				{
					// skipp bad link, for some hashtag or other stuff
					if (strpos($link->href, '/') == 0)
					{
						$link->href = substr($link->href, 1);
					}
					$link->href = self::URI . $link->href;
				}
			}
			$content = $content->innertext;

			$item['content'] .= '<div style="margin-top: -1.5em">' .  $content . '</div>';

			// extract plaintext
			$item['content_simple'] = $post->find('div.Al', 0)->plaintext;
		}

//		$html->save(__DIR__ . '/../posts2.html');
	}

	public function getName()
	{
		return $this->_title ?: 'Google Plus Post Bridge';
	}

	public function getURI()
	{
		return $this->_url ?: self::URI;
	}
}
