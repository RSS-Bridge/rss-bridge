<?php

/**
 * GooglePlusPostBridge
 * 2014-07-20
 *
 * @name Google Plus Post Bridge
 * @homepage http://plus.google.com/
 * @description Returns user public post (using their API because without you need to parse javascript ...).
 * @maintainer Grummfy
 * @use1(username="usernameOrId")
 */
class GooglePlusPostBridge extends BridgeAbstract
{
	public function collectData(array $param)
	{
		if (!isset($param['username']))
		{
			$this->returnError('You must specify a username (?username=...).', 400);
		}

		$this->request = $param['username'];
		//$html = file_get_html('https://plus.google.com/' . urlencode($this->request) . '/posts') or $this->returnError('No results for this query.', 404);
		$html = str_get_html(__DIR__ . '/../posts') or $this->returnError('No results for this query.', 404);

		//var_dump($html);
		//exit();
			$item = new \Item();
			$dsd = array();
			foreach (get_object_vars($html) as $k => $v)
			{
				$dsd[ $k ] = array_keys(get_object_vars($v));
			}
		$item->content = var_export($dsd, true);
		$this->items[] = $item;
			$item = new \Item();
		$item->content = var_export((($html->find('div.Dge.fOa'))), true);
		$this->items[] = $item;
			$item = new \Item();
			$item->content = var_export($html->find('div', 0), true) . $html->dump_node();
		$this->items[] = $item;
		foreach($html->find('div.Yp.yt.Xa') as $post)
		{
			$item = new \Item();
			$item->content = $post->find('dib.Al.pf')->innerHTML;
			$item->username = $item->fullname = $post->find('header.lea h3 a', 0)->innertext;
			$item->id = $post->getAttribute('id');
			$item->title = $item->fullname = $post->find('header.lea', 0)->innertext;
			$item->avatar = $post->find('.ys a.ob.Jk img', 0)->src;
			$item->uri = $post->find('a.o-U-s.FI.Rg')->href;
			$item->timestamp = $post->find('a.o-U-s.FI.Rg')->title; // 5 juin 2014 23:20:41
			$this->items[] = $item;
			break;
		}

//			// extract plaintext
//			$item->content_simple = str_replace('href="/', 'href="https://twitter.com/', html_entity_decode(strip_tags($tweet->find('p.js-tweet-text', 0)->innertext, '<a>')));
//
//			// generate the title
//			$item->title = $item->fullname . ' (@'. $item->username . ') | ' . $item->content_simple;
//			// put out
//			$this->items[] = $item;
//		}
	}

	public function getName()
	{
		return 'Google Plus Post Bridge';
	}

	public function getURI()
	{
		return 'http://plus.google.com/';
	}

	public function getCacheDuration()
	{
		return 1; // 600; // 10 minutes
	}
}
