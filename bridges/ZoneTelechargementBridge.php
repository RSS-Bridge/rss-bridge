<?php
class ZoneTelechargementBridge extends BridgeAbstract {

	/*  This bridge was initally done for the Website Zone Telechargement,
	 *  but the website changed it's name and URL.
	 *  Therefore, the class name and filename does not correspond to the
	 *  name of the bridge. This permits to keep the same RSS Feed URL.
	 */

	const NAME = 'Zone Telechargement';
	const URI = 'https://www.zone-telechargement.cam/';
	const DESCRIPTION = 'Suivi de série sur Zone Telechargement';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Suivre la publication des épisodes d\'une série en cours de diffusion' => array(
			'url' => array(
				'name' => 'URL de la série',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une série sans le https://www.zone-telechargement.cam/',
				'exampleValue' => 'telecharger-series/31079-halt-and-catch-fire-saison-4-french-hd720p.html'),
			'filter' => array(
				'name' => 'Type de contenu',
				'type' => 'list',
				'title' => 'Type de contenu à suivre : Téléchargement, Streaming ou les deux',
				'values' => array(
					'Streaming et Téléchargement' => 'both',
					'Téléchargement' => 'download',
					'Streaming' => 'streaming'
				),
				'defaultValue' => 'both'
			)
		)
	);

	// This is an URL that is not protected by robot protection for Direct Download
	const UNPROTECTED_URI = 'https://mail.zone-telechargement.cam/';

	// This is the CURL_RESOLVE array content
	private $resolve = array();

	// This function use curl library with curl as User Agent instead of
	// simple_html_dom to load the HTML content as the website has some captcha
	// request for other user agents
	// To bypass CloudFlare, we try to use use the CURLOPT_RESOLVE setting
	private function loadURL($url){
		$header = array();
		// Parse the URL to extract the hostname
		$parse = parse_url($url);
		$opts = array(
			CURLOPT_USERAGENT => Configuration::getConfig('http', 'useragent'),
			CURLOPT_RESOLVE => $this->getResolve($parse['host'])
		);

		$html = getContents($url, $header, $opts);
		return str_get_html($html);
	}

	public function getIcon(){
		return self::UNPROTECTED_URI . '/templates/Default/images/favicon.ico';
	}

	public function collectData(){
		$html = $this->loadURL(self::UNPROTECTED_URI . $this->getInput('url'));
		$filter = $this->getInput('filter');

		// Get the TV show title
		$qualityselector = 'div[style=font-size: 18px;margin: 10px auto;color:red;font-weight:bold;text-align:center;]';
		$show = trim($html->find('div[class=smallsep]', 0)->next_sibling()->plaintext);
		$quality = trim(explode("\n", $html->find($qualityselector, 0)->plaintext)[0]);
		$this->showTitle = $show . ' ' . $quality;

		$episodes = array();

		// Handle the Direct Download links
		if($filter == 'both' || $filter == 'download') {
			// Get the post content
			$linkshtml = $html->find('div[class=postinfo]', 0);

			$list = $linkshtml->find('a');
			// Construct the table of episodes using the links
			foreach($list as $element) {
				// Retrieve episode number from link text
				$epnumber = explode(' ', $element->plaintext)[1];
				$hoster = $this->findLinkHoster($element);

				// Format the link and add the link to the corresponding episode table
				$episodes[$epnumber]['ddl'][] = '<a href="' . $this->rewriteProtectedLink($element->href) . '">' . $hoster . ' - '
					. $this->showTitle . ' Episode ' . $epnumber . '</a>';

			}
		}

		// Handle the Streaming links
		if($filter == 'both' || $filter == 'streaming') {
			// Get the HTML element containing all the links
			$streaminglinkshtml = $html->find('p[style=background-color: #FECC00;]', 1)->parent()->next_sibling();
			// Get all streaming Links
			$liststreaming = $streaminglinkshtml->find('a');
			foreach($liststreaming as $elementstreaming) {
				// Retrieve the episode number from the link text
				$epnumber = explode(' ', $elementstreaming->plaintext)[1];

				// Format the link and add the link to the corresponding episode table
				$episodes[$epnumber]['streaming'][] = '<a href="' . $this->rewriteProtectedLink($elementstreaming->href) . '">'
					. $this->showTitle . ' Episode ' . $epnumber . '</a>';
			}
		}

		// Finally construct the items array
		foreach($episodes as $epnum => $episode) {
			// Handle the Direct Download links
			if(array_key_exists('ddl', $episode)) {
				$item = array();
				// Add every link available in the episode table separated by a <br/> tag
				$item['content'] = implode('<br/>', $episode['ddl']);
				$item['title'] = $this->showTitle . ' Episode ' . $epnum . ' - Téléchargement';
				// Generate an unique UID by hashing the item title to prevent confusion for RSS readers
				$item['uid'] = hash('md5', $item['title']);
				$item['uri'] = self::URI . $this->getInput('url');
				// Insert the episode at the beginning of the item list, to show the newest episode first
				array_unshift($this->items, $item);
			}
			// Handle the streaming link
			if(array_key_exists('streaming', $episode)) {
				$item = array();
				// Add every link available in the episode table separated by a <br/> tag
				$item['content'] = implode('<br/>', $episode['streaming']);
				$item['title'] = $this->showTitle . ' Episode ' . $epnum . ' - Streaming';
				// Generate an unique UID by hashing the item title to prevent confusion for RSS readers
				$item['uid'] = hash('md5', $item['title']);
				$item['uri'] = self::URI . $this->getInput('url');
				// Insert the episode at the beginning of the item list, to show the newest episode first
				array_unshift($this->items, $item);
			}
		}
	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
			return $this->showTitle . ' - ' . self::NAME;
			break;
		default:
			return self::NAME;
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
		case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
			return self::URI . $this->getInput('url');
			break;
		default:
			return self::URI;
		}
	}

	private function findLinkHoster($element) {
		// The hoster name is one level higher than the link tag : get the parent element
		$element = $element->parent();
		// Walk through all elements in the reverse order until finding the one with a div and that is not a <br/>
		while(!($element->find('div', 0) != null && $element->tag != 'br')) {
			$element = $element->prev_sibling();
		}
		// Return the text of the div : it's the file hoster name !
		return $element->find('div', 0)->plaintext;

	}

	// Rewrite the links to use the new URL Protection system
	private function rewriteProtectedLink($url)
	{
		// Split the link using '/'
		$parts = explode('/', $url);
		// return the new URL using the new Link Protection system
		return 'https://ztprotecte.cam//171-2/?link=' . end($parts);
	}

	// Function to get the CURL_RESOLVE content
	private function getResolve($hostname)
	{
		// If the proterty $this->resolve is an empty array, the we must find the "non cloudflare" IP
		if(count($this->resolve) == 0) {
			// This is a list of domains / subdomains that could have the real IP
			$alias = array('zone-telechargement.cam', 'www.zone-telechargement.cam',
				'www2.zone-telechargement.cam', 'dashboard.zone-telechargement.cam',
				'anycast.zone-telechargement.cam', 'admin.zone-telechargement.cam',
				'app.zone-telechargement.cam', 'panel.zone-telechargement.cam',
				'embed.zone-telechargement.cam', 'autoconfig.zone-telechargement.cam',
				'autodiscover.zone-telechargement.cam', 'private.zone-telechargement.cam',
				'mail.zone-telechargement.cam', 'direct.zone-telechargement.cam',
				'direct-connect.zone-telechargement.cam', 'cpanel.zone-telechargement.cam',
				'ftp.zone-telechargement.cam', 'pop.zone-telechargement.cam',
				'imap.zone-telechargement.cam', 'forum.zone-telechargement.cam',
				'blog.zone-telechargement.cam', 'portal.zone-telechargement.cam',
				'beta.zone-telechargement.cam', 'dev.zone-telechargement.cam',
				'webmail.zone-telechargement.cam', 'record.zone-telechargement.cam',
				'ssl.zone-telechargement.cam', 'dns.zone-telechargement.cam',
				'ts3.zone-telechargement.cam', 'm.zone-telechargement.cam',
				'mobile.zone-telechargement.cam', 'help.zone-telechargement.cam',
				'wiki.zone-telechargement.cam', 'client.zone-telechargement.cam',
				'server.zone-telechargement.cam', 'api.zone-telechargement.cam',
				'i.zone-telechargement.cam', 'x.zone-telechargement.cam',
				'cdn.zone-telechargement.cam', 'images.zone-telechargement.cam',
				'my.zone-telechargement.cam', 'java.zone-telechargement.cam',
				'swf.zone-telechargement.cam', 'smtp.zone-telechargement.cam',
				'ns.zone-telechargement.cam', 'ns1.zone-telechargement.cam',
				'ns2.zone-telechargement.cam', 'ns3.zone-telechargement.cam',
				'mx.zone-telechargement.cam', 'server1.zone-telechargement.cam',
				'server2.zone-telechargement.cam', 'test.zone-telechargement.cam',
				'vpn.zone-telechargement.cam', 'secure.zone-telechargement.cam',
				'login.zone-telechargement.cam', 'store.zone-telechargement.cam',
				'zabbix.zone-telechargement.cam', 'cacti.zone-telechargement.cam',
				'mysql.zone-telechargement.cam', 'search.zone-telechargement.cam',
				'monitor.zone-telechargement.cam', 'nagios.zone-telechargement.cam',
				'munin.zone-telechargement.cam', 'data.zone-telechargement.cam',
				'old.zone-telechargement.cam', 'stat.zone-telechargement.cam',
				'stats.zone-telechargement.cam', 'preview.zone-telechargement.cam',
				'phpmyadmin.zone-telechargement.cam', 'db.zone-telechargement.cam',
				'demo.zone-telechargement.cam', 'status.zone-telechargement.cam',
				'gateway.zone-telechargement.cam', 'gateway1.zone-telechargement.cam',
				'gateway2.zone-telechargement.cam', 'sip.zone-telechargement.cam',
				'remote.zone-telechargement.cam', 'svn.zone-telechargement.cam',
				'git.zone-telechargement.cam', 'release.zone-telechargement.cam',
				'support.zone-telechargement.cam', 'jira.zone-telechargement.cam',
				'confluence.zone-telechargement.cam', 'reader.zone-telechargement.cam',
				'jobs.zone-telechargement.cam', 'cloud.zone-telechargement.cam',
				'cmd.zone-telechargement.cam', 'exchange.zone-telechargement.cam',
				'ip.zone-telechargement.cam', 'mc.zone-telechargement.cam',
				'mx1.zone-telechargement.cam', 'owa.zone-telechargement.cam',
				'play.zone-telechargement.cam', 'proxy.zone-telechargement.cam',
				'shop.zone-telechargement.cam', 'tc.zone-telechargement.cam',
				'vps.zone-telechargement.cam');

			// found will be false until we found an IP not from cloudflare
			$found = false;
			// Count of alias
			$aliascount = count($alias);
			$i = 0;

			while($found == false && $i < $aliascount) {
				$name = $alias[$i];
				$ip = gethostbyname($name);

				// if $name could be resolved
				if($name != $ip) {
					$found = !$this->ipv4InCloudflareRange($ip);
				}
				$i++;
			}

			// If we could found an IP not from Cloudflare then store it in the resolve property
			if($found) {
				$this->resolve = array($hostname . ':443:' . $ip);
			}
		}
		return $this->resolve;
	}

	// Based on https://github.com/cloudflarearchive/Cloudflare-Tools/blob/master/cloudflare/ip_in_range.php
	// Checks if an IP is in a IP range (only with CIDR notation)
	private function ipv4InRange($ip, $range) {
		list($range, $netmask) = explode('/', $range, 2);
		// $netmask is a CIDR size block
		// fix the range argument
		$x = explode('.', $range);
		while(count($x) < 4) {
			$x[] = '0';
		}
		list($a,$b,$c,$d) = $x;
		$range = sprintf('%u.%u.%u.%u', empty($a) ? '0' : $a, empty($b) ? '0' : $b,
			empty($c) ? '0' : $c, empty($d) ? '0' : $d);
		$range_dec = ip2long($range);
		$ip_dec = ip2long($ip);

		$wildcard_dec = pow(2, (32 - $netmask)) - 1;
		$netmask_dec = ~ $wildcard_dec;

		return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
	}

	// Check if an IP is within the IPv4 Cloudflare ranges
	private function ipv4InCloudflareRange($ip) {
		// Cloudflare IPv4 Range
		// Based on https://bgp.tools/as/13335
		// Agregated using https://tehnoblog.org/ip-tools/ip-address-aggregator/
		$ranges = array('1.0.0.0/24',
			'1.1.1.0/24', '8.6.112.0/24', '8.6.144.0/23', '8.6.146.0/24', '8.9.230.0/23',
			'8.10.148.0/24', '8.14.199.0/24', '8.14.201.0/24', '8.14.202.0/23', '8.14.204.0/24',
			'8.17.205.0/24', '8.17.206.0/24', '8.18.50.0/24', '8.18.113.0/24', '8.18.194.0/23',
			'8.18.196.0/24', '8.20.100.0/23', '8.20.103.0/24', '8.20.122.0/23', '8.20.124.0/22',
			'8.20.253.0/24', '8.21.8.0/22', '8.21.13.0/24', '8.21.110.0/23', '8.21.238.0/23',
			'8.23.139.0/24', '8.23.240.0/24', '8.24.87.0/24', '8.24.242.0/23', '8.24.244.0/24',
			'8.25.96.0/23', '8.25.249.0/24', '8.26.176.0/24', '8.26.180.0/24', '8.26.182.0/24',
			'8.27.64.0/24', '8.27.66.0/23', '8.27.68.0/23', '8.27.70.0/24', '8.27.79.0/24',
			'8.28.20.0/24', '8.28.82.0/24', '8.28.126.0/24', '8.28.213.0/24', '8.29.105.0/24',
			'8.29.109.0/24', '8.29.228.0/24', '8.29.230.0/23', '8.30.234.0/24', '8.31.2.0/24',
			'8.31.160.0/23', '8.31.163.0/24', '8.34.69.0/24', '8.34.70.0/23', '8.34.146.0/24',
			'8.34.200.0/24', '8.34.202.0/24', '8.35.57.0/24', '8.35.58.0/23', '8.35.149.0/24',
			'8.35.211.0/24', '8.36.216.0/23', '8.36.218.0/24', '8.36.220.0/24', '8.37.41.0/24',
			'8.37.43.0/24', '8.38.147.0/24', '8.38.148.0/23', '8.38.172.0/24', '8.39.6.0/24',
			'8.39.18.0/24', '8.39.125.0/24', '8.39.126.0/23', '8.39.201.0/24', '8.39.202.0/23',
			'8.39.204.0/22', '8.39.212.0/22', '8.40.26.0/23', '8.40.28.0/22', '8.40.107.0/24',
			'8.40.111.0/24', '8.40.140.0/24', '8.41.5.0/24', '8.41.6.0/23', '8.41.36.0/23',
			'8.41.39.0/24', '8.42.51.0/24', '8.42.52.0/24', '8.42.54.0/23', '8.42.161.0/24',
			'8.42.164.0/24', '8.42.172.0/24', '8.42.245.0/24', '8.43.121.0/24', '8.43.122.0/23',
			'8.43.224.0/23', '8.43.226.0/24', '8.44.0.0/22', '8.44.6.0/24', '8.44.58.0/23',
			'8.44.60.0/22', '8.45.41.0/24', '8.45.42.0/23', '8.45.44.0/22', '8.45.97.0/24',
			'8.45.100.0/23', '8.45.102.0/24', '8.45.108.0/24', '8.45.111.0/24', '8.45.144.0/22',
			'8.45.151.0/24', '8.46.113.0/24', '8.46.114.0/23', '8.46.116.0/22', '8.47.9.0/24',
			'8.47.12.0/22', '8.47.71.0/24', '8.48.130.0/24', '8.48.132.0/23', '8.48.134.0/24',
			'23.227.37.0/24', '23.227.38.0/23', '64.68.192.0/24', '64.179.227.0/24', '64.179.228.0/24',
			'65.110.63.0/24', '66.235.200.0/24', '68.67.65.0/24', '91.234.214.0/24', '103.21.244.0/24',
			'103.22.200.0/23', '103.22.203.0/24', '103.81.228.0/24', '104.16.0.0/12', '108.162.192.0/20',
			'108.162.208.0/24', '108.162.210.0/23', '108.162.212.0/23', '108.162.216.0/22',
			'108.162.220.0/23', '108.162.223.0/24', '108.162.228.0/23', '108.162.235.0/24',
			'108.162.236.0/22', '108.162.240.0/21', '108.162.248.0/23', '108.162.250.0/24',
			'108.162.253.0/24', '141.101.64.0/21', '141.101.72.0/22', '141.101.76.0/23', '141.101.82.0/23',
			'141.101.84.0/23', '141.101.94.0/23', '141.101.96.0/21', '141.101.104.0/22',
			'141.101.108.0/23', '141.101.110.0/24', '141.101.112.0/20', '162.158.0.0/22', '162.158.4.0/23',
			'162.158.8.0/21', '162.158.16.0/20', '162.158.32.0/22', '162.158.36.0/23', '162.158.38.0/24',
			'162.158.40.0/21', '162.158.48.0/24', '162.158.50.0/23', '162.158.52.0/22', '162.158.56.0/21',
			'162.158.76.0/22', '162.158.80.0/20', '162.158.96.0/20', '162.158.112.0/23',
			'162.158.114.0/24', '162.158.116.0/22', '162.158.120.0/21', '162.158.128.0/19',
			'162.158.160.0/21', '162.158.168.0/22', '162.158.176.0/20', '162.158.192.0/22',
			'162.158.196.0/23', '162.158.198.0/24', '162.158.200.0/21', '162.158.208.0/22',
			'162.158.212.0/24', '162.158.214.0/23', '162.158.216.0/21', '162.158.224.0/19',
			'162.159.0.0/18', '162.159.64.0/20', '162.159.128.0/17', '162.247.243.0/24', '162.251.82.0/24',
			'172.64.0.0/15', '172.66.40.0/21', '172.67.0.0/16', '172.68.0.0/19', '172.68.32.0/21',
			'172.68.40.0/22', '172.68.48.0/21', '172.68.60.0/22', '172.68.68.0/22', '172.68.72.0/21',
			'172.68.80.0/20', '172.68.96.0/20', '172.68.112.0/23', '172.68.114.0/24', '172.68.116.0/22',
			'172.68.120.0/23', '172.68.124.0/22', '172.68.128.0/20', '172.68.148.0/22', '172.68.152.0/22',
			'172.68.166.0/23', '172.68.168.0/21', '172.68.176.0/23', '172.68.179.0/24', '172.68.180.0/22',
			'172.68.184.0/21', '172.68.196.0/22', '172.68.200.0/21', '172.68.212.0/22', '172.68.220.0/22',
			'172.68.224.0/19', '172.69.0.0/20', '172.69.16.0/24', '172.69.19.0/24', '172.69.20.0/22',
			'172.69.32.0/20', '172.69.48.0/24', '172.69.52.0/22', '172.69.56.0/22', '172.69.64.0/19',
			'172.69.96.0/21', '172.69.108.0/22', '172.69.112.0/21', '172.69.124.0/22', '172.69.128.0/20',
			'172.69.144.0/21', '172.69.156.0/22', '172.69.160.0/19', '172.69.192.0/20', '172.69.208.0/24',
			'172.69.210.0/23', '172.69.212.0/22', '172.69.216.0/21', '172.69.224.0/20', '172.69.240.0/21',
			'172.69.248.0/24', '172.69.252.0/22', '172.70.32.0/21', '172.70.40.0/22', '172.70.44.0/23',
			'172.70.48.0/20', '172.70.80.0/20', '172.70.96.0/20', '172.70.112.0/22', '172.70.116.0/23',
			'172.70.120.0/21', '172.70.128.0/19', '172.70.160.0/22', '172.70.172.0/22', '172.70.176.0/21',
			'172.70.184.0/24', '172.70.186.0/23', '172.70.188.0/22', '172.70.192.0/22', '172.70.196.0/23',
			'172.70.198.0/24', '172.70.200.0/21', '172.70.208.0/20', '172.70.224.0/24', '172.70.227.0/24',
			'172.70.228.0/22', '172.70.232.0/21', '172.70.240.0/20', '172.71.1.0/24', '172.71.2.0/24',
			'172.71.4.0/22', '172.71.8.0/22', '172.71.12.0/23', '172.71.15.0/24', '172.71.16.0/24',
			'173.245.49.0/24', '173.245.52.0/24', '173.245.58.0/23', '173.245.63.0/24', '185.146.172.0/24',
			'188.114.96.0/21', '188.114.106.0/23', '188.114.108.0/22', '190.93.240.0/20',
			'195.242.122.0/23', '197.234.240.0/22', '198.41.128.0/23', '198.41.192.0/20',
			'198.41.208.0/23', '198.41.211.0/24', '198.41.212.0/24', '198.41.214.0/23', '198.41.216.0/21',
			'198.41.224.0/21', '198.41.232.0/23', '198.41.235.0/24', '198.41.236.0/22', '198.41.240.0/23',
			'198.41.242.0/24', '198.217.251.0/24', '199.27.128.0/22', '199.27.132.0/24');

		$iscloudflare = false;

		// For each Cloudflare Range, check if the IP is in this range
		foreach($ranges as $range) {
			$iscloudflare |= $this->ipv4InRange($ip, $range);

			// If the IP is from Cloudflare, we don't need to test the IP against other ranges
			if($iscloudflare) {
				break;
			}
		}
		return $iscloudflare;
	}
}
