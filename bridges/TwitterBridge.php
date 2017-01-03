<?php
class TwitterBridge extends BridgeAbstract{
    const NAME='Twitter Bridge';
    const URI='https://twitter.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION='returns tweets';
	const MAINTAINER = 'pmaziere';
    const PARAMETERS=array(
        'global'=>array(
            'nopic'=>array(
                'name'=>'Hide profile pictures',
                'type'=>'checkbox',
                'title'=>'Activate to hide profile pictures in content'
            )
        ),
        'By keyword or hashtag' => array(
            'q'=>array(
                'name'=>'Keyword or #hashtag',
                'required'=>true,
                'exampleValue'=>'rss-bridge, #rss-bridge',
                'title'=>'Insert a keyword or hashtag'
            )
        ),
        'By username' => array(
            'u'=>array(
                'name'=>'username',
                'required'=>true,
                'exampleValue'=>'sebsauvage',
                'title'=>'Insert a user name'
            ),
            'norep'=>array(
                'name'=>'Without replies',
                'type'=>'checkbox',
                'title'=>'Only return initial tweets'
            )
        )
    );

    public function getName(){
        switch($this->queriedContext){
        case 'By keyword or hashtag':
            $specific='search ';
            $param='q';
            break;
        case 'By username':
            $specific='@';
            $param='u';
            break;
        }
        return 'Twitter '.$specific.$this->getInput($param);
    }

    public function getURI(){
        switch($this->queriedContext){
        case 'By keyword or hashtag':
            return self::URI.'search?q='.urlencode($this->getInput('q')).'&f=tweets';
        case 'By username':
            return self::URI.urlencode($this->getInput('u')).
                ($this->getInput('norep')?'':'/with_replies');
        }
    }

	public function collectData(){
		$html = '';

		$html = getSimpleHTMLDOM($this->getURI());
		if(!$html){
			switch($this->queriedContext){
			case 'By keyword or hashtag':
				returnServerError('No results for this query.');
			case 'By username':
				returnServerError('Requested username can\'t be found.');
			}
		}

		$hidePictures = $this->getInput('nopic');

		foreach($html->find('div.js-stream-tweet') as $tweet) {
			$item = array();
			// extract username and sanitize
			$item['username'] = $tweet->getAttribute('data-screen-name');
			// extract fullname (pseudonym)
			$item['fullname'] = $tweet->getAttribute('data-name');
			// get author
			$item['author'] = $item['fullname'] . ' (@' . $item['username'] . ')';
			// get avatar link
			$item['avatar'] = $tweet->find('img', 0)->src;
			// get TweetID
			$item['id'] = $tweet->getAttribute('data-tweet-id');
			// get tweet link
			$item['uri'] = self::URI.$tweet->find('a.js-permalink', 0)->getAttribute('href');
			// extract tweet timestamp
			$item['timestamp'] = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// generate the title
			$item['title'] = strip_tags(html_entity_decode($tweet->find('p.js-tweet-text', 0)->innertext,ENT_QUOTES,'UTF-8'));

			// processing content links
			foreach($tweet->find('a') as $link) {
				if($link->hasAttribute('data-expanded-url') ) {
					$link->href = $link->getAttribute('data-expanded-url');
				}
				$link->removeAttribute('data-expanded-url');
				$link->removeAttribute('data-query-source');
				$link->removeAttribute('rel');
				$link->removeAttribute('class');
				$link->removeAttribute('target');
				$link->removeAttribute('title');
			}

			// process emojis (reduce size)
			foreach($tweet->find('img.Emoji') as $img){
				$img->style .= ' height: 1em;';
			}

			// get tweet text
			$cleanedTweet = str_replace('href="/', 'href="'.self::URI, $tweet->find('p.js-tweet-text', 0)->innertext);

			// Add picture to content
			$picture_html = '';
			if(!$hidePictures){
				$picture_html = <<<EOD
<a href="https://twitter.com/{$item['username']}"><img style="align: top; width:75 px; border: 1px solid black;" alt="{$item['username']}" src="{$item['avatar']}" title="{$item['fullname']}" /></a>
EOD;
			}

			// add content
			$item['content'] = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	{$picture_html}
</div>
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedTweet}</blockquote>
</div>
EOD;

			// put out
			$this->items[] = $item;
		}
	}
}
