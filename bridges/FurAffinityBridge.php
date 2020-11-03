<?php
class FurAffinityBridge extends BridgeAbstract {
	const NAME = 'FurAffinity Bridge';
	const URI = 'https://www.furaffinity.net';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Returns posts from various sections of FurAffinity';
	const MAINTAINER = 'Roliga';
	const PARAMETERS = array(
		'Search' => array(
			'q' => array(
				'name' => 'Query',
				'required' => true
			),
			'rating-general' => array(
				'name' => 'General',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'rating-mature' => array(
				'name' => 'Mature',
				'type' => 'checkbox',
			),
			'rating-adult' => array(
				'name' => 'Adult',
				'type' => 'checkbox',
			),
			'range' => array(
				'name' => 'Time range',
				'type' => 'list',
				'values' => array(
					'A Day' => 'day',
					'3 Days' => '3days',
					'A Week' => 'week',
					'A Month' => 'month',
					'All time' => 'all'
				),
				'defaultValue' => 'all'
			),
			'type-art' => array(
				'name' => 'Art',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'type-flash' => array(
				'name' => 'Flash',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'type-photo' => array(
				'name' => 'Photography',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'type-music' => array(
				'name' => 'Music',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'type-story' => array(
				'name' => 'Story',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'type-poetry' => array(
				'name' => 'Poetry',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'mode' => array(
				'name' => 'Match mode',
				'type' => 'list',
				'values' => array(
					'All of the words' => 'all',
					'Any of the words' => 'any',
					'Extended' => 'extended'
				),
				'defaultValue' => 'extended'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)
		),
		'Browse' => array(
			'cat' => array(
				'name' => 'Category',
				'type' => 'list',
				'values' => array(
					'Visual Art' => array(
						'All' => 1,
						'Artwork (Digital)' => 2,
						'Artwork (Traditional)' => 3,
						'Cellshading' => 4,
						'Crafting' => 5,
						'Designs' => 6,
						'Flash' => 7,
						'Fursuiting' => 8,
						'Icons' => 9,
						'Mosaics' => 10,
						'Photography' => 11,
						'Sculpting' => 12
					),
					'Readable Art' => array(
						'Story' => 13,
						'Poetry' => 14,
						'Prose' => 15
					),
					'Audio Art' => array(
						'Music' => 16,
						'Podcasts' => 17
					),
					'Downloadable' => array(
						'Skins' => 18,
						'Handhelds' => 19,
						'Resources' => 20
					),
					'Other Stuff' => array(
						'Adoptables' => 21,
						'Auctions' => 22,
						'Contests' => 23,
						'Current Events' => 24,
						'Desktops' => 25,
						'Stockart' => 26,
						'Screenshots' => 27,
						'Scraps' => 28,
						'Wallpaper' => 29,
						'YCH / Sale' => 30,
						'Other' => 31
					)
				),
				'defaultValue' => 1
			),
			'atype' => array(
				'name' => 'Type',
				'type' => 'list',
				'values' => array(
					'General Things' => array(
						'All' => 1,
						'Abstract' => 2,
						'Animal related (non-anthro)' => 3,
						'Anime' => 4,
						'Comics' => 5,
						'Doodle' => 6,
						'Fanart' => 7,
						'Fantasy' => 8,
						'Human' => 9,
						'Portraits' => 10,
						'Scenery' => 11,
						'Still Life' => 12,
						'Tutorials' => 13,
						'Miscellaneous' => 14
					),
					'Fetish / Furry specialty' => array(
						'Baby fur' => 101,
						'Bondage' => 102,
						'Digimon' => 103,
						'Fat Furs' => 104,
						'Fetish Other' => 105,
						'Fursuit' => 106,
						'Gore / Macabre Art' => 119,
						'Hyper' => 107,
						'Inflation' => 108,
						'Macro / Micro' => 109,
						'Muscle' => 110,
						'My Little Pony / Brony' => 111,
						'Paw' => 112,
						'Pokemon' => 113,
						'Pregnancy' => 114,
						'Sonic' => 115,
						'Transformation' => 116,
						'Vore' => 117,
						'Water Sports' => 118,
						'General Furry Art' => 100
					),
					'Music' => array(
						'Techno' => 201,
						'Trance' => 202,
						'House' => 203,
						'90s' => 204,
						'80s' => 205,
						'70s' => 206,
						'60s' => 207,
						'Pre-60s' => 208,
						'Classical' => 209,
						'Game Music' => 210,
						'Rock' => 211,
						'Pop' => 212,
						'Rap' => 213,
						'Industrial' => 214,
						'Other Music' => 200
					)
				),
				'defaultValue' => 1
			),
			'species' => array(
				'name' => 'Species',
				'type' => 'list',
				'values' => array(
					'Unspecified / Any' => 1,
					'Amphibian' => array(
						'Frog' => 1001,
						'Newt' => 1002,
						'Salamander' => 1003,
						'Amphibian (Other)' => 1000
					),
					'Aquatic' => array(
						'Cephalopod' => 2001,
						'Dolphin' => 2002,
						'Fish' => 2005,
						'Porpoise' => 2004,
						'Seal' => 6068,
						'Shark' => 2006,
						'Whale' => 2003,
						'Aquatic (Other)' => 2000
					),
					'Avian' => array(
						'Corvid' => 3001,
						'Crow' => 3002,
						'Duck' => 3003,
						'Eagle' => 3004,
						'Falcon' => 3005,
						'Goose' => 3006,
						'Gryphon' => 3007,
						'Hawk' => 3008,
						'Owl' => 3009,
						'Phoenix' => 3010,
						'Swan' => 3011,
						'Avian (Other)' => 3000
					),
					'Bears &amp; Ursines' => array(
						'Bear' => 6002
					),
					'Camelids' => array(
						'Camel' => 6074,
						'Llama' => 6036
					),
					'Canines &amp; Lupines' => array(
						'Coyote' => 6008,
						'Doberman' => 6009,
						'Dog' => 6010,
						'Dingo' => 6011,
						'German Shepherd' => 6012,
						'Jackal' => 6013,
						'Husky' => 6014,
						'Wolf' => 6016,
						'Canine (Other)' => 6017
					),
					'Cervines' => array(
						'Cervine (Other)' => 6018
					),
					'Cows &amp; Bovines' => array(
						'Antelope' => 6004,
						'Cows' => 6003,
						'Gazelle' => 6005,
						'Goat' => 6006,
						'Bovines (General)' => 6007
					),
					'Dragons' => array(
						'Eastern Dragon' => 4001,
						'Hydra' => 4002,
						'Serpent' => 4003,
						'Western Dragon' => 4004,
						'Wyvern' => 4005,
						'Dragon (Other)' => 4000
					),
					'Equestrians' => array(
						'Donkey' => 6019,
						'Horse' => 6034,
						'Pony' => 6073,
						'Zebra' => 6071
					),
					'Exotic &amp; Mythicals' => array(
						'Argonian' => 5002,
						'Chakat' => 5003,
						'Chocobo' => 5004,
						'Citra' => 5005,
						'Crux' => 5006,
						'Daemon' => 5007,
						'Digimon' => 5008,
						'Dracat' => 5009,
						'Draenei' => 5010,
						'Elf' => 5011,
						'Gargoyle' => 5012,
						'Iksar' => 5013,
						'Kaiju/Monster' => 5015,
						'Langurhali' => 5014,
						'Moogle' => 5017,
						'Naga' => 5016,
						'Orc' => 5018,
						'Pokemon' => 5019,
						'Satyr' => 5020,
						'Sergal' => 5021,
						'Tanuki' => 5022,
						'Unicorn' => 5023,
						'Xenomorph' => 5024,
						'Alien (Other)' => 5001,
						'Exotic (Other)' => 5000
					),
					'Felines' => array(
						'Domestic Cat' => 6020,
						'Cheetah' => 6021,
						'Cougar' => 6022,
						'Jaguar' => 6023,
						'Leopard' => 6024,
						'Lion' => 6025,
						'Lynx' => 6026,
						'Ocelot' => 6027,
						'Panther' => 6028,
						'Tiger' => 6029,
						'Feline (Other)' => 6030
					),
					'Insects' => array(
						'Arachnid' => 8000,
						'Mantid' => 8004,
						'Scorpion' => 8005,
						'Insect (Other)' => 8003
					),
					'Mammals (Other)' => array(
						'Bat' => 6001,
						'Giraffe' => 6031,
						'Hedgehog' => 6032,
						'Hippopotamus' => 6033,
						'Hyena' => 6035,
						'Panda' => 6052,
						'Pig/Swine' => 6053,
						'Rabbit/Hare' => 6059,
						'Raccoon' => 6060,
						'Red Panda' => 6062,
						'Meerkat' => 6043,
						'Mongoose' => 6044,
						'Rhinoceros' => 6063,
						'Mammals (Other)' => 6000
					),
					'Marsupials' => array(
						'Opossum' => 6037,
						'Kangaroo' => 6038,
						'Koala' => 6039,
						'Quoll' => 6040,
						'Wallaby' => 6041,
						'Marsupial (Other)' => 6042
					),
					'Mustelids' => array(
						'Badger' => 6045,
						'Ferret' => 6046,
						'Mink' => 6048,
						'Otter' => 6047,
						'Skunk' => 6069,
						'Weasel' => 6049,
						'Mustelid (Other)' => 6051
					),
					'Primates' => array(
						'Gorilla' => 6054,
						'Human' => 6055,
						'Lemur' => 6056,
						'Monkey' => 6057,
						'Primate (Other)' => 6058
					),
					'Reptillian' => array(
						'Alligator &amp; Crocodile' => 7001,
						'Gecko' => 7003,
						'Iguana' => 7004,
						'Lizard' => 7005,
						'Snakes &amp; Serpents' => 7006,
						'Turtle' => 7007,
						'Reptilian (Other)' => 7000
					),
					'Rodents' => array(
						'Beaver' => 6064,
						'Mouse' => 6065,
						'Rat' => 6061,
						'Squirrel' => 6070,
						'Rodent (Other)' => 6067
					),
					'Vulpines' => array(
						'Fennec' => 6072,
						'Fox' => 6075,
						'Vulpine (Other)' => 6015
					),
					'Other' => array(
						'Dinosaur' => 8001,
						'Wolverine' => 6050
					)
				),
				'defaultValue' => 1
			),
			'gender' => array(
				'name' => 'Gender',
				'type' => 'list',
				'values' => array(
					'Any' => 0,
					'Male' => 2,
					'Female' => 3,
					'Herm' => 4,
					'Transgender' => 5,
					'Multiple characters' => 6,
					'Other / Not Specified' => 7
				),
				'defaultValue' => 0
			),
			'rating_general' => array(
				'name' => 'General',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'rating_mature' => array(
				'name' => 'Mature',
				'type' => 'checkbox',
			),
			'rating_adult' => array(
				'name' => 'Adult',
				'type' => 'checkbox',
			),
			'limit-browse' => array(
				'name' => 'Limit',
				'type' => 'number',
				'required' => true,
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)

		),
		'Journals' => array(
			'username-journals' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Lowercase username as seen in URLs'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => -1,
				'title' => 'Limit number of journals to return. -1 for unlimited.'
			)

		),
		'Single Journal' => array(
			'journal-id' => array(
				'name' => 'Journal ID',
				'required' => true,
				'type' => 'number',
				'title' => 'Number seen in journal URL'
			)
		),
		'Gallery' => array(
			'username-gallery' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Lowercase username as seen in URLs'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)
		),
		'Scraps' => array(
			'username-scraps' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Lowercase username as seen in URLs'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)
		),
		'Favorites' => array(
			'username-favorites' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Lowercase username as seen in URLs'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)
		),
		'Gallery Folder' => array(
			'username-folder' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Lowercase username as seen in URLs'
			),
			'folder-id' => array(
				'name' => 'Folder ID',
				'required' => true,
				'type' => 'number',
				'title' => 'Number seen in folder URL'
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'title' => 'Limit number of submissions to return. -1 for unlimited.'
			),
			'full' => array(
				'name' => 'Full view',
				'title' => 'Include description, tags, date and larger image in article. Uses more bandwidth.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			),
			'cache' => array(
				'name' => 'Cache submission pages',
				'title' => 'Reduces requests to FA when Full view is enabled. Changes to submission details may be delayed.',
				'type' => 'checkbox',
				'defaultValue' => 'checked'
			)
		)
	);

	/*
	 * This was aquired by creating a new user on FA then
	 * extracting the cookie from the browsers dev console.
	 */
	const FA_AUTH_COOKIE = 'b=4ce65691-b50f-4742-a990-bf28d6de16ee; a=ca6e4566-9d81-4263-9444-653b142e35f8';

	public function detectParameters($url) {
		$params = array();

		// Single journal
		$regex = '/^(https?:\/\/)?(www\.)?furaffinity.net\/journal\/(\d+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['journal-id'] = urldecode($matches[3]);
			return $params;
		}

		// Journals
		$regex = '/^(https?:\/\/)?(www\.)?furaffinity.net\/journals\/([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['username-journals'] = urldecode($matches[3]);
			return $params;
		}

		// Gallery folder
		$regex = '/^(https?:\/\/)?(www\.)?furaffinity.net\/gallery\/([^\/&?\n]+)\/folder\/(\d+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['username-folder'] = urldecode($matches[3]);
			$params['folder-id'] = urldecode($matches[4]);
			$params['full'] = 'on';
			return $params;
		}

		// Gallery (must be after gallery folder)
		$regex = '/^(https?:\/\/)?(www\.)?furaffinity.net\/(gallery|scraps|favorites)\/([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['username-' . $matches[3]] = urldecode($matches[4]);
			$params['full'] = 'on';
			return $params;
		}

		return null;
	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Search':
			return 'Search For '
				. $this->getInput('q');
		case 'Browse':
			return 'Browse';
		case 'Journals':
			return $this->getInput('username-journals');
		case 'Single Journal':
			return 'Journal '
				. $this->getInput('journal-id');
		case 'Gallery':
			return $this->getInput('username-gallery');
		case 'Scraps':
			return $this->getInput('username-scraps');
		case 'Favorites':
			return $this->getInput('username-favorites');
		case 'Gallery Folder':
			return $this->getInput('username-folder')
				. '\'s Folder '
				. $this->getInput('folder-id');
		default: return parent::getName();
		}
	}

	public function getDescription() {
		switch($this->queriedContext) {
		case 'Search':
			return 'FurAffinity Search For '
				. $this->getInput('q');
		case 'Browse':
			return 'FurAffinity Browse';
		case 'Journals':
			return 'FurAffinity Journals By '
				. $this->getInput('username-journals');
		case 'Single Journal':
			return 'FurAffinity Journal '
				. $this->getInput('journal-id');
		case 'Gallery':
			return 'FurAffinity Gallery By '
				. $this->getInput('username-gallery');
		case 'Scraps':
			return 'FurAffinity Scraps By '
				. $this->getInput('username-scraps');
		case 'Favorites':
			return 'FurAffinity Favorites By '
				. $this->getInput('username-favorites');
		case 'Gallery Folder':
			return 'FurAffinity Gallery Folder '
				. $this->getInput('folder-id')
				. ' By '
				. $this->getInput('username-folder');
		default: return parent::getDescription();
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
		case 'Search':
			return SELF::URI
				. '/search';
		case 'Browse':
			return SELF::URI
				. '/browse';
		case 'Journals':
			return SELF::URI
				. '/journals/'
				. $this->getInput('username-journals');
		case 'Single Journal':
			return SELF::URI
				. '/journal/'
				. $this->getInput('journal-id');
		case 'Gallery':
			return SELF::URI
				. '/gallery/'
				. $this->getInput('username-gallery');
		case 'Scraps':
			return SELF::URI
				. '/scraps/'
				. $this->getInput('username-scraps');
		case 'Favorites':
			return SELF::URI
				. '/favorites/'
				. $this->getInput('username-favorites');
		case 'Gallery Folder':
			return SELF::URI
				. '/gallery/'
				. $this->getInput('username-folder')
				. '/folder/'
				. $this->getInput('folder-id');
		default: return parent::getURI();
		}
	}

	public function collectData() {
		switch($this->queriedContext) {
		case 'Search':
			$data = array(
				'q' => $this->getInput('q'),
				'perpage' => 72,
				'rating-general' => ($this->getInput('rating-general') === true ? 'on' : 0),
				'rating-mature' => ($this->getInput('rating-mature') === true ? 'on' : 0),
				'rating-adult' => ($this->getInput('rating-adult') === true ? 'on' : 0),
				'range' => $this->getInput('range'),
				'type-art' => ($this->getInput('type-art') === true ? 'on' : 0),
				'type-flash' => ($this->getInput('type-flash') === true ? 'on' : 0),
				'type-photo' => ($this->getInput('type-photo') === true ? 'on' : 0),
				'type-music' => ($this->getInput('type-music') === true ? 'on' : 0),
				'type-story' => ($this->getInput('type-story') === true ? 'on' : 0),
				'type-poetry' => ($this->getInput('type-poetry') === true ? 'on' : 0),
				'mode' => $this->getInput('mode')
			);
			$html = $this->postFASimpleHTMLDOM($data);
			$limit = (is_int($this->getInput('limit')) ? $this->getInput('limit') : 10);
			$this->itemsFromSubmissionList($html, $limit);
			break;
		case 'Browse':
			$data = array(
				'cat' => $this->getInput('cat'),
				'atype' => $this->getInput('atype'),
				'species' => $this->getInput('species'),
				'gender' => $this->getInput('gender'),
				'perpage' => 72,
				'rating_general' => ($this->getInput('rating_general') === true ? 'on' : 0),
				'rating_mature' => ($this->getInput('rating_mature') === true ? 'on' : 0),
				'rating_adult' => ($this->getInput('rating_adult') === true ? 'on' : 0)
			);
			$html = $this->postFASimpleHTMLDOM($data);
			$limit = (is_int($this->getInput('limit-browse')) ? $this->getInput('limit-browse') : 10);
			$this->itemsFromSubmissionList($html, $limit);
			break;
		case 'Journals':
			$html = $this->getFASimpleHTMLDOM($this->getURI());
			$limit = (is_int($this->getInput('limit')) ? $this->getInput('limit') : -1);
			$this->itemsFromJournalList($html, $limit);
			break;
		case 'Single Journal':
			$html = $this->getFASimpleHTMLDOM($this->getURI());
			$this->itemsFromJournal($html);
			break;
		case 'Gallery':
		case 'Scraps':
		case 'Favorites':
		case 'Gallery Folder':
			$html = $this->getFASimpleHTMLDOM($this->getURI());
			$limit = (is_int($this->getInput('limit')) ? $this->getInput('limit') : 10);
			$this->itemsFromSubmissionList($html, $limit);
			break;
		}
	}

	private function postFASimpleHTMLDOM($data) {
			$opts = array(
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => http_build_query($data)
			);
			$header = array(
				'Host: ' . parse_url(self::URI, PHP_URL_HOST),
				'Content-Type: application/x-www-form-urlencoded',
				'Cookie: ' . self::FA_AUTH_COOKIE
			);

			$html = getSimpleHTMLDOM($this->getURI(), $header, $opts);
			$html = defaultLinkTo($html, $this->getURI());

			return $html;
	}

	private function getFASimpleHTMLDOM($url, $cache = false) {
			$header = array(
				'Cookie: ' . self::FA_AUTH_COOKIE
			);

			if($cache) {
				$html = getSimpleHTMLDOMCached($url, 86400, $header); // 24 hours
			} else {
				$html = getSimpleHTMLDOM($url, $header);
			}

			$html = defaultLinkTo($html, $url);

			return $html;
	}

	private function itemsFromJournalList($html, $limit) {
		foreach($html->find('table[id^=jid:]') as $journal) {
			# allows limit = -1 to mean 'unlimited'
			if($limit-- === 0) break;

			$item = array();

			$this->setReferrerPolicy($journal);

			$item['uri'] = $journal->find('a', 0)->href;
			$item['title'] = html_entity_decode($journal->find('a', 0)->plaintext);
			$item['author'] = $this->getInput('username-journals');
			$item['timestamp'] = strtotime(
				$journal->find('span.popup_date', 0)->plaintext);
			$item['content'] = $journal
				->find('.alt1 table div.no_overflow', 0)
				->innertext;

			$this->items[] = $item;
		}
	}

	private function itemsFromJournal($html) {
		$this->setReferrerPolicy($html);
		$item = array();

		$item['uri'] = $this->getURI();

		$title = $html->find('.journal-title-box .no_overflow', 0)->plaintext;
		$title = html_entity_decode($title);
		$title = trim($title, " \t\n\r\0\x0B" . chr(0xC2) . chr(0xA0));
		$item['title'] = $title;

		$item['author'] = $html->find('.journal-title-box a', 0)->plaintext;
		$item['timestamp'] = strtotime(
			$html->find('.journal-title-box span.popup_date', 0)->plaintext);
		$item['content'] = $html->find('.journal-body', 0)->innertext;

		$this->items[] = $item;
	}

	private function itemsFromSubmissionList($html, $limit) {
		$cache = ($this->getInput('cache') === true);

		foreach($html->find('section.gallery figure') as $figure) {
			# allows limit = -1 to mean 'unlimited'
			if($limit-- === 0) break;

			$item = array();

			$submissionURL = $figure->find('b u a', 0)->href;
			$imgURL = 'https:' . $figure->find('b u a img', 0)->src;

			$item['uri'] = $submissionURL;
			$item['title'] = html_entity_decode(
				$figure->find('figcaption p a[href*=/view/]', 0)->title);
			$item['author'] = $figure->find('figcaption p a[href*=/user/]', 0)->title;

			if($this->getInput('full') === true) {
				$submissionHTML = $this->getFASimpleHTMLDOM($submissionURL, $cache);

				$stats = $submissionHTML->find('.stats-container', 0);
				$item['timestamp'] = strtotime($stats->find('.popup_date', 0)->title);
				$item['enclosures'] = array(
					$submissionHTML->find('.actions a[href^=https://d.facdn]', 0)->href
				);
				foreach($stats->find('#keywords a') as $keyword) {
					$item['categories'][] = $keyword->plaintext;
				}

				$previewSrc = $submissionHTML->find('#submissionImg', 0)
					->{'data-preview-src'};
				if($previewSrc) {
					$imgURL = 'https:' . $previewSrc;
				}

				$description = $submissionHTML
					->find('.maintable .maintable tr td.alt1', -1);
				$this->setReferrerPolicy($description);
				$description = $description->innertext;

				$item['content'] = <<<EOD
<a href="$submissionURL">
	<img src="{$imgURL}" referrerpolicy="no-referrer" />
</a>
<p>
{$description}
</p>
EOD;
			} else {
				$item['content'] = <<<EOD
<a href="$submissionURL">
	<img src="$imgURL" referrerpolicy="no-referrer" />
</a>
EOD;
			}

			$this->items[] = $item;
		}
	}

	private function setReferrerPolicy(&$html) {
		foreach($html->find('img') as $img) {
			/*
			 * Note: Without the no-referrer policy their CDN sometimes denies requests.
			 * We can't control this for enclosures sadly.
			 * At least tt-rss adds the referrerpolicy on its own.
			 * Alternatively we could not use https for images, but that's not ideal.
			 */
			$img->referrerpolicy = 'no-referrer';
		}
	}
}
