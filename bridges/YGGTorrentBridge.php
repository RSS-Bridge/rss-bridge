<?php

/* This is a mashup of FlickrExploreBridge by sebsauvage and FlickrTagBridge
 * by erwang.providing the functionality of both in one.
 */
class YGGTorrentBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'Yggtorrent Bridge';
	const URI = 'https://yggtorrent.is';
	const DESCRIPTION = 'Returns torrent search from Yggtorrent';

	const PARAMETERS = array(
		array(
			'cat' => array(
				'name' => 'category',
				'type' => 'list',
				'values' => array(
					'Toute les catégories' => 'all.all',
					'Film/Vidéo - Toutes les sous-catégories' => '2145.all',
					'Film/Vidéo - Animation' => '2145.2178',
					'Film/Vidéo - Animation Série' => '2145.2179',
					'Film/Vidéo - Concert' => '2145.2180',
					'Film/Vidéo - Documentaire' => '2145.2181',
					'Film/Vidéo - Émission TV' => '2145.2182',
					'Film/Vidéo - Film' => '2145.2183',
					'Film/Vidéo - Série TV' => '2145.2184',
					'Film/Vidéo - Spectacle' => '2145.2185',
					'Film/Vidéo - Sport' => '2145.2186',
					'Film/Vidéo - Vidéo-clips' => '2145.2186',
					'Audio - Toutes les sous-catégories' => '2139.all',
					'Audio - Karaoké' => '2139.2147',
					'Audio - Musique' => '2139.2148',
					'Audio - Podcast Radio' => '2139.2150',
					'Audio - Samples' => '2139.2149',
					'Jeu vidéo - Toutes les sous-catégories' => '2142.all',
					'Jeu vidéo - Autre' => '2142.2167',
					'Jeu vidéo - Linux' => '2142.2159',
					'Jeu vidéo - MacOS' => '2142.2160',
					'Jeu vidéo - Microsoft' => '2142.2162',
					'Jeu vidéo - Nintendo' => '2142.2163',
					'Jeu vidéo - Smartphone' => '2142.2165',
					'Jeu vidéo - Sony' => '2142.2164',
					'Jeu vidéo - Tablette' => '2142.2166',
					'Jeu vidéo - Windows' => '2142.2161',
					'eBook - Toutes les sous-catégories' => '2140.all',
					'eBook - Audio' => '2140.2151',
					'eBook - Bds' => '2140.2152',
					'eBook - Comics' => '2140.2153',
					'eBook - Livres' => '2140.2154',
					'eBook - Mangas' => '2140.2155',
					'eBook - Presse' => '2140.2156',
					'Emulation - Toutes les sous-catégories' => '2141.all',
					'Emulation - Emulateurs' => '2141.2157',
					'Emulation - Roms' => '2141.2158',
					'GPS - Toutes les sous-catégories' => '2141.all',
					'GPS - Applications' => '2141.2168',
					'GPS - Cartes' => '2141.2169',
					'GPS - Divers' => '2141.2170'
				)
			),
			'nom' => array(
				'name' => 'Nom',
				'description' => 'Nom du torrent',
				'type' => 'text'
			),
			'description' => array(
				'name' => 'Description',
				'description' => 'Description du torrent',
				'type' => 'text'
			),
			'fichier' => array(
				'name' => 'Fichier',
				'description' => 'Fichier du torrent',
				'type' => 'text'
			),
			'uploader' => array(
				'name' => 'Uploader',
				'description' => 'Uploader du torrent',
				'type' => 'text'
			),

		)
	);

	public function collectData() {

		$catInfo = explode('.', $this->getInput('cat'));
		$category = $catInfo[0];
		$subcategory = $catInfo[1];

		$html = getSimpleHTMLDOM(self::URI . '/engine/search?name='
					. $this->getInput('nom')
					. '&description='
					. $this->getInput('description')
					. '&fichier='
					. $this->getInput('fichier')
					. '&file='
					. $this->getInput('uploader')
					. '&category='
					. $category
					. '&sub_category='
					. $subcategory
					. '&do=search&order=desc&sort=publish_date')
					or returnServerError('Unable to query Yggtorrent !');

		$count = 0;
		$results = $html->find('.results', 0);
		if(!$results) return;

		foreach($results->find('tr') as $row) {
			$count++;
			if($count == 1) continue; // Skip table header
			if($count == 22) break; // Stop processing after 21 items (20 + 1 table header)
			$item = array();
			$item['timestamp'] = $row->find('.hidden', 1)->plaintext;
			$item['title'] = $row->find('a', 1)->plaintext;
			$torrentData = $this->collectTorrentData($row->find('a', 1)->href);
			$item['author'] = $torrentData['author'];
			$item['content'] = $torrentData['content'];
			$item['seeders'] = $row->find('td', 7)->plaintext;
			$item['leechers'] = $row->find('td', 8)->plaintext;
			$item['size'] = $row->find('td', 5)->plaintext;

			$this->items[] = $item;
		}

	}

	private function collectTorrentData($url) {

		//For weird reason, the link we get can be invalid, we fix it.
		$url_full = explode('/', $url);
		$url_full[4] = urlencode($url_full[4]);
		$url_full[5] = urlencode($url_full[5]);
		$url_full[6] = urlencode($url_full[6]);
		$url = implode('/', $url_full);
		$page = getSimpleHTMLDOMCached($url) or returnServerError('Unable to query Yggtorrent page !');
		$author = $page->find('.informations', 0)->find('a', 4)->plaintext;
		$content = $page->find('.default', 1);
		return array('author' => $author, 'content' => $content);
	}
}
