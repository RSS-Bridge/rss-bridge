<?php
/**
* RssBridgeTwitter
* Based on https://github.com/mitsukarenai/twitterbridge-noapi
* 2014-12-05
*
* @name Twitter Bridge Tweaked
* @homepage https://twitter.com/
* @description (same as Twitter Bridge Extended, but with cleaned title & content)
* @maintainer kraoc
* @use1(q="keyword or hashtag")
* @use2(u="username")
*/

class TwitterBridgeTweaked extends BridgeAbstract{

    private function containsTLD($string) {
        preg_match(
            "/(AC($|\/)|\.AD($|\/)|\.AE($|\/)|\.AERO($|\/)|\.AF($|\/)|\.AG($|\/)|\.AI($|\/)|\.AL($|\/)|\.AM($|\/)|\.AN($|\/)|\.AO($|\/)|\.AQ($|\/)|\.AR($|\/)|\.ARPA($|\/)|\.AS($|\/)|\.ASIA($|\/)|\.AT($|\/)|\.AU($|\/)|\.AW($|\/)|\.AX($|\/)|\.AZ($|\/)|\.BA($|\/)|\.BB($|\/)|\.BD($|\/)|\.BE($|\/)|\.BF($|\/)|\.BG($|\/)|\.BH($|\/)|\.BI($|\/)|\.BIZ($|\/)|\.BJ($|\/)|\.BM($|\/)|\.BN($|\/)|\.BO($|\/)|\.BR($|\/)|\.BS($|\/)|\.BT($|\/)|\.BV($|\/)|\.BW($|\/)|\.BY($|\/)|\.BZ($|\/)|\.CA($|\/)|\.CAT($|\/)|\.CC($|\/)|\.CD($|\/)|\.CF($|\/)|\.CG($|\/)|\.CH($|\/)|\.CI($|\/)|\.CK($|\/)|\.CL($|\/)|\.CM($|\/)|\.CN($|\/)|\.CO($|\/)|\.COM($|\/)|\.COOP($|\/)|\.CR($|\/)|\.CU($|\/)|\.CV($|\/)|\.CX($|\/)|\.CY($|\/)|\.CZ($|\/)|\.DE($|\/)|\.DJ($|\/)|\.DK($|\/)|\.DM($|\/)|\.DO($|\/)|\.DZ($|\/)|\.EC($|\/)|\.EDU($|\/)|\.EE($|\/)|\.EG($|\/)|\.ER($|\/)|\.ES($|\/)|\.ET($|\/)|\.EU($|\/)|\.FI($|\/)|\.FJ($|\/)|\.FK($|\/)|\.FM($|\/)|\.FO($|\/)|\.FR($|\/)|\.GA($|\/)|\.GB($|\/)|\.GD($|\/)|\.GE($|\/)|\.GF($|\/)|\.GG($|\/)|\.GH($|\/)|\.GI($|\/)|\.GL($|\/)|\.GM($|\/)|\.GN($|\/)|\.GOV($|\/)|\.GP($|\/)|\.GQ($|\/)|\.GR($|\/)|\.GS($|\/)|\.GT($|\/)|\.GU($|\/)|\.GW($|\/)|\.GY($|\/)|\.HK($|\/)|\.HM($|\/)|\.HN($|\/)|\.HR($|\/)|\.HT($|\/)|\.HU($|\/)|\.ID($|\/)|\.IE($|\/)|\.IL($|\/)|\.IM($|\/)|\.IN($|\/)|\.INFO($|\/)|\.INT($|\/)|\.IO($|\/)|\.IQ($|\/)|\.IR($|\/)|\.IS($|\/)|\.IT($|\/)|\.JE($|\/)|\.JM($|\/)|\.JO($|\/)|\.JOBS($|\/)|\.JP($|\/)|\.KE($|\/)|\.KG($|\/)|\.KH($|\/)|\.KI($|\/)|\.KM($|\/)|\.KN($|\/)|\.KP($|\/)|\.KR($|\/)|\.KW($|\/)|\.KY($|\/)|\.KZ($|\/)|\.LA($|\/)|\.LB($|\/)|\.LC($|\/)|\.LI($|\/)|\.LK($|\/)|\.LR($|\/)|\.LS($|\/)|\.LT($|\/)|\.LU($|\/)|\.LV($|\/)|\.LY($|\/)|\.MA($|\/)|\.MC($|\/)|\.MD($|\/)|\.ME($|\/)|\.MG($|\/)|\.MH($|\/)|\.MIL($|\/)|\.MK($|\/)|\.ML($|\/)|\.MM($|\/)|\.MN($|\/)|\.MO($|\/)|\.MOBI($|\/)|\.MP($|\/)|\.MQ($|\/)|\.MR($|\/)|\.MS($|\/)|\.MT($|\/)|\.MU($|\/)|\.MUSEUM($|\/)|\.MV($|\/)|\.MW($|\/)|\.MX($|\/)|\.MY($|\/)|\.MZ($|\/)|\.NA($|\/)|\.NAME($|\/)|\.NC($|\/)|\.NE($|\/)|\.NET($|\/)|\.NF($|\/)|\.NG($|\/)|\.NI($|\/)|\.NL($|\/)|\.NO($|\/)|\.NP($|\/)|\.NR($|\/)|\.NU($|\/)|\.NZ($|\/)|\.OM($|\/)|\.ORG($|\/)|\.PA($|\/)|\.PE($|\/)|\.PF($|\/)|\.PG($|\/)|\.PH($|\/)|\.PK($|\/)|\.PL($|\/)|\.PM($|\/)|\.PN($|\/)|\.PR($|\/)|\.PRO($|\/)|\.PS($|\/)|\.PT($|\/)|\.PW($|\/)|\.PY($|\/)|\.QA($|\/)|\.RE($|\/)|\.RO($|\/)|\.RS($|\/)|\.RU($|\/)|\.RW($|\/)|\.SA($|\/)|\.SB($|\/)|\.SC($|\/)|\.SD($|\/)|\.SE($|\/)|\.SG($|\/)|\.SH($|\/)|\.SI($|\/)|\.SJ($|\/)|\.SK($|\/)|\.SL($|\/)|\.SM($|\/)|\.SN($|\/)|\.SO($|\/)|\.SR($|\/)|\.ST($|\/)|\.SU($|\/)|\.SV($|\/)|\.SY($|\/)|\.SZ($|\/)|\.TC($|\/)|\.TD($|\/)|\.TEL($|\/)|\.TF($|\/)|\.TG($|\/)|\.TH($|\/)|\.TJ($|\/)|\.TK($|\/)|\.TL($|\/)|\.TM($|\/)|\.TN($|\/)|\.TO($|\/)|\.TP($|\/)|\.TR($|\/)|\.TRAVEL($|\/)|\.TT($|\/)|\.TV($|\/)|\.TW($|\/)|\.TZ($|\/)|\.UA($|\/)|\.UG($|\/)|\.UK($|\/)|\.US($|\/)|\.UY($|\/)|\.UZ($|\/)|\.VA($|\/)|\.VC($|\/)|\.VE($|\/)|\.VG($|\/)|\.VI($|\/)|\.VN($|\/)|\.VU($|\/)|\.WF($|\/)|\.WS($|\/)|\.XN--0ZWM56D($|\/)|\.XN--11B5BS3A9AJ6G($|\/)|\.XN--80AKHBYKNJ4F($|\/)|\.XN--9T4B11YI5A($|\/)|\.XN--DEBA0AD($|\/)|\.XN--G6W251D($|\/)|\.XN--HGBK6AJ7F53BBA($|\/)|\.XN--HLCJ6AYA9ESC7A($|\/)|\.XN--JXALPDLP($|\/)|\.XN--KGBECHTV($|\/)|\.XN--ZCKZAH($|\/)|\.YE($|\/)|\.YT($|\/)|\.YU($|\/)|\.ZA($|\/)|\.ZM($|\/)|\.ZW)/i",
            $string,
            $M
        );
        $has_tld = (count($M) > 0) ? true : false;
        return $has_tld;
    }
    private function cleaner($url) {
        $U = explode(' ', $url);
        $W =array();
        foreach ($U as $k => $u) {
            if (stristr($u,".")) { //only preg_match if there is a dot
                if ($this->containsTLD($u) === true) {
                    unset($U[$k]);
                    return $this->cleaner( implode(' ', $U) );
                }
            }
        }
        return implode(' ', $U);
    }

    // (c) Kraoc / urlclean
    // https://github.com/kraoc/Leed-market/blob/master/urlclean/urlclean.plugin.disabled.php
    private function resolve_url($link) {
        // fallback to crawl to real url (slowest method and unsecure to privacy)
        if (function_exists('curl_init') && !ini_get('safe_mode')) {
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // >>> anonimization
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);
            curl_setopt($ch, CURLOPT_REFERER, '');
            // <<< anonimization
            $ch = curl_init();
            $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16';
            $a = curl_exec($ch);
            $link = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        }

        $link = preg_replace("/[&#?]xtor=(.)+/", "", $link); // remove: xtor
        $link = preg_replace("/utm_([^&#]|(&amp;))+&*/", "", $link); // remove: utm_

        // cleanup end of url
        $link = preg_replace("/\?&/", "", $link);
        if (isset($link[strlen($link) -1])){
            if ($link[strlen($link) -1] == '?')
                $link = substr($link, 0, strlen($link) -1);
        }

        return $link;
    }

	public function collectData(array $param){
		$html = '';
		if (isset($param['q'])) {   /* keyword search mode */
			$html = file_get_html('https://twitter.com/search/realtime?q='.urlencode($param['q']).'+include:retweets&src=typd') or $this->returnError('No results for this query.', 404);
		}
		elseif (isset($param['u'])) {   /* user timeline mode */
			$html = file_get_html('https://twitter.com/'.urlencode($param['u']).'/with_replies') or $this->returnError('Requested username can\'t be found.', 404);
		}
		else {
			$this->returnError('You must specify a keyword (?q=...) or a Twitter username (?u=...).', 400);
		}

		foreach($html->find('div.js-stream-tweet') as $tweet) {
			$item = new \Item();
			// extract username and sanitize
			$item->username = $tweet->getAttribute('data-screen-name');
			// extract fullname (pseudonym)
			$item->fullname = $tweet->getAttribute('data-name');
			// get avatar link
			$item->avatar = $tweet->find('img', 0)->src;
			// get TweetID
			$item->id = $tweet->getAttribute('data-tweet-id');
			// get tweet link
			$item->uri = 'https://twitter.com'.$tweet->find('a.js-permalink', 0)->getAttribute('href');
			// extract tweet timestamp
			$item->timestamp = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// extract plaintext
			$item->content_simple = str_replace('href="/', 'href="https://twitter.com/', html_entity_decode(strip_tags($tweet->find('p.js-tweet-text', 0)->innertext, '<a>')));

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

			// get tweet text
			$item->content = '<a href="https://twitter.com/'.$item->username.'"><img style="align:top;width:75px;" alt="avatar" src="'.$item->avatar.'" />'.$item->username.'</a> '.$item->fullname.'<br/><blockquote>'.str_replace('href="/', 'href="https://twitter.com/', $tweet->find('p.js-tweet-text', 0)->innertext).'</blockquote>';
			// generate the title
//			$item->title = $item->fullname . ' (@'. $item->username . ') | ' . $item->content_simple;
            $item->title = $item->content_simple;
            $item->title = preg_replace('|https?://www\.[a-z\.0-9]+|i', '', $item->title); // remove http(s) links
            $item->title = preg_replace('|www\.[a-z\.0-9]+|i', '', $item->title); // remove www. links
            $item->title = $this->cleaner($item->title); // remove all remaining links
            $item->title = trim($item->title); // remove extra spaces at beginning and end

            // convert all content links to real ones
            $regex = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
            $item->content = preg_replace_callback($regex, function($url) {
                // do stuff with $url[0] here
                return $this->resolve_url($url[0]);
            }, $item->content);

			// put out
			$this->items[] = $item;
		}
	}

	public function getName(){
		return 'Twitter Bridge Tweaked';
	}

	public function getURI(){
		return 'http://twitter.com';
	}

	public function getCacheDuration(){
		return 300; // 5 minutes
	}

	public function getUsername(){
		return $this->items[0]->username;
	}
}
