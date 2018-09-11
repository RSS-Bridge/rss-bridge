<?php
class DealabsBridge extends PepperBridgeAbstract {

	const NAME = 'Dealabs Bridge';
	const URI = 'https://www.dealabs.com/';
	const DESCRIPTION = 'Affiche les Deals de Dealabs';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Recherche par Mot(s) clé(s)' => array (
			'q' => array(
				'name' => 'Mot(s) clé(s)',
				'type' => 'text',
				'required' => true
			),
			'hide_expired' => array(
				'name' => 'Masquer les éléments expirés',
				'type' => 'checkbox',
				'required' => 'true'
			),
			'hide_local' => array(
				'name' => 'Masquer les deals locaux',
				'type' => 'checkbox',
				'title' => 'Masquer les deals en magasins physiques',
				'required' => 'true'
			),
			'priceFrom' => array(
				'name' => 'Prix minimum',
				'type' => 'text',
				'title' => 'Prix mnimum en euros',
				'required' => 'false',
				'defaultValue' => ''
			),
			'priceTo' => array(
				'name' => 'Prix maximum',
				'type' => 'text',
				'title' => 'Prix maximum en euros',
				'required' => 'false',
				'defaultValue' => ''
			),
		),

		'Deals par groupe' => array(
			'group' => array(
				'name' => 'Groupe',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Groupe dont il faut afficher les deals',
				'values' => array(
					'Abonnements internet' => 'abonnements-internet',
					'Accessoires & gadgets' => 'accessoires-gadgets',
					'Accessoires photo' => 'accessoires-photo',
					'Accessoires vélo' => 'accessoires-velo',
					'Acer' => 'acer',
					'Adaptateurs' => 'adaptateurs',
					'Adhérents Fnac' => 'adherents-fnac',
					'adidas' => 'adidas',
					'adidas Stan Smith' => 'adidas-stan-smith',
					'adidas Superstar' => 'adidas-superstar',
					'adidas ZX Flux' => 'adidas-zx-flux',
					'Adoucissant' => 'adoucissant',
					'Agendas' => 'agendas',
					'Age of Empires' => 'age-of-empires',
					'Alarmes' => 'alarmes',
					'Alimentation & boissons' => 'alimentation-boissons',
					'Alimentation PC' => 'alimentation-pc',
					'Amazon Echo' => 'amazon-echo',
					'Amazon Fire TV' => 'amazon-fire-tv',
					'Amazon Kindle' => 'amazon-kindle',
					'Amazon Prime' => 'amazon-prime',
					'AMD Ryzen' => 'amd-ryzen',
					'AMD Vega' => 'amd-vega',
					'amiibo' => 'amiibo',
					'Amplis' => 'amplis',
					'Ampoules' => 'ampoules',
					'Animaux' => 'animaux',
					'Anker' => 'anker',
					'Antivirus' => 'antivirus',
					'Antivols' => 'antivols',
					'Appareils de musculation' => 'appareils-de-musculation',
					'Appareils photo' => 'appareils-photo',
					'Apple AirPods' => 'apple-airpods',
					'Apple' => 'apple',
					'Apple iPad' => 'apple-ipad',
					'Apple iPad Mini' => 'apple-ipad-mini',
					'Apple iPad Pro' => 'apple-ipad-pro',
					'Apple iPhone 6' => 'apple-iphone-6',
					'Apple iPhone 7' => 'apple-iphone-7',
					'Apple iPhone 8' => 'apple-iphone-8',
					'Apple iPhone 8 Plus' => 'apple-iphone-8-plus',
					'Apple iPhone' => 'apple-iphone',
					'Apple iPhone SE' => 'apple-iphone-se',
					'Apple iPhone X' => 'apple-iphone-x',
					'Apple MacBook Air' => 'apple-macbook-air',
					'Apple MacBook Pro' => 'apple-macbook-pro',
					'Apple TV' => 'apple-tv',
					'Apple Watch' => 'apple-watch',
					'Applications Android' => 'applications-android',
					'Applications' => 'applications',
					'Applications iOS' => 'applications-ios',
					'Applis & logiciels' => 'applis-logiciels',
					'Arbres à chat' => 'arbres-a-chat',
					'Asmodée' => 'asmodee',
					'Aspirateurs' => 'aspirateurs',
					'Aspirateurs Dyson' => 'aspirateurs-dyson',
					'Aspirateurs robot' => 'aspirateurs-robot',
					'Assassin&#039;s Creed' => 'assassin-s-creed',
					'Assassin&#039;s Creed Origins' => 'assassin-s-creed-origins',
					'Assurances' => 'assurances',
					'Asus' => 'asus',
					'ASUS Transformer' => 'asus-transformer',
					'Asus ZenFone 2' => 'asus-zenfone-2',
					'Asus ZenFone 3' => 'asus-zenfone-3',
					'Asus ZenFone 4' => 'asus-zenfone-4',
					'Asus ZenFone GO' => 'asus-zenfone-go',
					'Aukey' => 'aukey',
					'Auto' => 'auto',
					'Auto-Moto' => 'auto-moto',
					'Autoradios' => 'autoradios',
					'Baby foot' => 'baby-foot',
					'BabyLiss' => 'babyliss',
					'Babyphones' => 'babyphones',
					'Bagagerie' => 'bagagerie',
					'Balançoires' => 'balancoires',
					'Bandes dessinées' => 'bandes-dessinees',
					'Banques' => 'banques',
					'Barbecue' => 'barbecue',
					'Barbie' => 'barbie',
					'Barres de son' => 'barres-de-son',
					'Batteries externes' => 'batteries-externes',
					'Battlefield 1' => 'battlefield-1',
					'Battlefield' => 'battlefield',
					'Béaba' => 'beaba',
					'Beats by Dre' => 'beats-by-dre',
					'BenQ' => 'benq',
					'Be quiet!' => 'be-quiet',
					'Biberons' => 'biberons',
					'Bières' => 'bieres',
					'Bijoux' => 'bijoux',
					'Billets d&#039;avion' => 'billets-d-avion',
					'BioShock' => 'bioshock',
					'BioShock Infinite' => 'bioshock-infinite',
					'Bitdefender' => 'bitdefender',
					'Blackberry' => 'blackberry',
					'Black & Decker' => 'black-decker',
					'Blédina' => 'bledina',
					'Blu-Ray' => 'blu-ray',
					'Boissons' => 'boissons',
					'Boîtes à outils' => 'boites-a-outils',
					'Boîtiers PC' => 'boitiers-pc',
					'Bonbons' => 'bonbons',
					'Borderlands' => 'borderlands',
					'Bosch' => 'bosch',
					'Bose' => 'bose',
					'Bose SoundLink' => 'bose-soundlink',
					'Bottes' => 'bottes',
					'Box beauté' => 'box-beaute',
					'Bracelet fitness' => 'bracelet-fitness',
					'Brandt' => 'brandt',
					'Braun Silk Épil' => 'braun-silk-epil',
					'Bricolage' => 'bricolage',
					'Brosses à dents' => 'brosses-a-dents',
					'Cable management' => 'cable-management',
					'Câbles' => 'cables',
					'Câbles HDMI' => 'cables-hdmi',
					'Câbles USB' => 'cables-usb',
					'Cadres' => 'cadres',
					'Café' => 'cafe',
					'Café en grain' => 'cafe-en-grain',
					'Cafetières' => 'cafetieres',
					'Cahiers' => 'cahiers',
					'Call of Duty' => 'call-of-duty',
					'Call of Duty: Infinite Warfare' => 'call-of-duty-infinite-warfare',
					'Calor' => 'calor',
					'Caméras' => 'cameras',
					'Caméras IP' => 'cameras-ip',
					'Camping' => 'camping',
					'Carburant' => 'carburant',
					'Cartables' => 'cartables',
					'Cartes graphiques' => 'cartes-graphiques',
					'Cartes mères' => 'cartes-meres',
					'Cartes postales' => 'cartes-postales',
					'Casques audio' => 'casques-audio',
					'Casques sans fil' => 'casques-sans-fil',
					'Casquettes' => 'casquettes',
					'Casseroles' => 'casseroles',
					'CDAV' => 'cdav',
					'Ceintures' => 'ceintures',
					'Chaises' => 'chaises',
					'Chaises hautes' => 'chaises-hautes',
					'Chargeurs' => 'chargeurs',
					'Chasse' => 'chasse',
					'Chats' => 'chats',
					'Chaussons' => 'chaussons',
					'Chaussures adidas' => 'chaussures-adidas',
					'Chaussures' => 'chaussures',
					'Chaussures de football' => 'chaussures-de-football',
					'Chaussures de randonnée' => 'chaussures-de-randonnee',
					'Chaussures de running' => 'chaussures-de-running',
					'Chaussures de ski' => 'chaussures-de-ski',
					'Chaussures de ville' => 'chaussures-de-ville',
					'Chaussures Nike' => 'chaussures-nike',
					'Chelsea boots' => 'chelsea-boots',
					'Chemises' => 'chemises',
					'Chiens' => 'chiens',
					'Chocolat' => 'chocolat',
					'Chuck Taylor' => 'chuck-taylor',
					'Cinéma' => 'cinema',
					'Civilization' => 'civilization',
					'Civilization VI' => 'civilization-vi',
					'Clarks' => 'clarks',
					'Claviers' => 'claviers',
					'Claviers gamer' => 'claviers-gamer',
					'Claviers mécaniques' => 'claviers-mecaniques',
					'Clés USB' => 'cles-usb',
					'Composteurs' => 'composteurs',
					'Concerts' => 'concerts',
					'Congélateurs' => 'congelateurs',
					'Consoles' => 'consoles',
					'Consoles & jeux vidéo' => 'consoles-jeux-video',
					'Converse' => 'converse',
					'Costumes' => 'costumes',
					'Couches' => 'couches',
					'Couettes' => 'couettes',
					'Couteaux de cuisine' => 'couteaux-de-cuisine',
					'Couverts' => 'couverts',
					'Covoiturage' => 'covoiturage',
					'Crédits' => 'credits',
					'Croquettes pour chien' => 'croquettes-pour-chien',
					'Cuisinières' => 'cuisinieres',
					'Culture & divertissement' => 'culture-divertissement',
					'Cyclisme' => 'cyclisme',
					'DDR3' => 'ddr3',
					'DDR4' => 'ddr4',
					'Décoration' => 'decoration',
					'Deezer' => 'deezer',
					'Dell' => 'dell',
					'Delsey' => 'delsey',
					'Denon' => 'denon',
					'Dentifrices' => 'dentifrices',
					'Destiny 2' => 'destiny-2',
					'Destiny' => 'destiny',
					'Dishonored' => 'dishonored',
					'Disneyland Paris' => 'disneyland-paris',
					'Disques durs externes' => 'disques-durs-externes',
					'Disques durs internes' => 'disques-durs',
					'DJI' => 'dji',
					'Dosettes Nespresso' => 'dosettes-nespresso',
					'Dosettes Senseo' => 'dosettes-senseo',
					'Dosettes Tassimo' => 'dosettes-tassimo',
					'Draisiennes' => 'draisiennes',
					'Drones' => 'drones',
					'Durex' => 'durex',
					'DVD' => 'dvd',
					'Dyson' => 'dyson',
					'Eastpak' => 'eastpak',
					'ebooks' => 'ebooks',
					'Écharpes & foulards' => 'echarpes-et-foulards',
					'Écouteurs' => 'ecouteurs',
					'Écouteurs intra-auriculaires' => 'ecouteurs-intra-auriculaires',
					'Écouteurs sans fil' => 'ecouteurs-sans-fil',
					'Écouteurs sport' => 'ecouteurs-sport',
					'Écrans 21" et moins' => 'ecrans-21-pouces-et-moins',
					'Écrans 24"' => 'ecrans-24-pouces',
					'Écrans 27"' => 'ecrans-27-pouces',
					'Écrans 29" et plus' => 'ecrans-29-pouces-et-plus',
					'Écrans 4K / UHD' => 'ecrans-4k-uhd',
					'Écrans Acer' => 'ecrans-acer',
					'Écrans Asus' => 'ecrans-asus',
					'Écrans BenQ' => 'ecrans-benq',
					'Écrans Dell' => 'ecrans-dell',
					'Écrans de projection' => 'ecrans-de-projection',
					'Écrans' => 'ecrans',
					'Écrans FreeSync' => 'ecrans-freesync',
					'Écrans gamer' => 'ecrans-gamer',
					'Écrans incurvés' => 'ecrans-incurves',
					'Écrans Philips' => 'ecrans-philips',
					'Écrans Samsung' => 'ecrans-samsung',
					'Électricité (matériel)' => 'electricite',
					'Electrolux' => 'electrolux',
					'Électroménager' => 'electromenager',
					'Embauchoirs' => 'embauchoirs',
					'Enceintes Bluetooth' => 'enceintes-bluetooth',
					'Enceintes' => 'enceintes',
					'Engrais' => 'engrais',
					'Entretien du jardin' => 'entretien-du-jardin',
					'Épicerie' => 'epicerie',
					'Épilateurs à lumière pulsée' => 'epilateurs-a-lumiere-pulsee',
					'Épilateurs électriques' => 'epilateurs-electriques',
					'Épilation' => 'epilation',
					'Équipement auto' => 'equipement-auto',
					'Équipement motard' => 'equipement-motard',
					'Équipement sportif' => 'equipement-sportif',
					'Érotisme' => 'erotisme',
					'Escarpins' => 'escarpins',
					'Événements sportifs' => 'evenements-sportifs',
					'Expositions' => 'expositions',
					'F1 2017' => 'f1-2017',
					'Facom' => 'facom',
					'Fallout 4' => 'fallout-4',
					'Fallout' => 'fallout',
					'Fards à paupières' => 'fards-a-paupieres',
					'Fast-foods' => 'fast-foods',
					'Fauteuils' => 'fauteuils',
					'Fers à lisser / à friser' => 'fers-a-lisser-a-friser',
					'Fers à souder' => 'fers-a-souder',
					'Festivals' => 'festivals',
					'Feutres' => 'feutres',
					'FIFA 17' => 'fifa-17',
					'FIFA 18' => 'fifa-18',
					'FIFA 19' => 'fifa-19',
					'FIFA' => 'fifa',
					'Figurines' => 'figurines',
					'Films' => 'films',
					'Final Fantasy' => 'final-fantasy',
					'Final Fantasy XII' => 'final-fantasy-xii',
					'fitbit' => 'fitbit',
					'Flash' => 'flash',
					'Fluval' => 'fluval',
					'Foires & salons' => 'foires-et-salons',
					'Fonds de teint' => 'fonds-de-teint',
					'Football' => 'football',
					'Forfaits mobiles' => 'forfaits-mobiles',
					'For Honor' => 'for-honor',
					'Formule 1' => 'formule-1',
					'Fortnite' => 'fortnite',
					'Forza Horizon 3' => 'forza-horizon-3',
					'Forza Motorsport 7' => 'forza-motorsport-7',
					'Fossil' => 'fossil',
					'Fournitures de bureau' => 'fournitures-de-bureau',
					'Fournitures scolaires' => 'fournitures-scolaires',
					'Fours à poser' => 'fours-a-poser',
					'Fours encastrables' => 'fours-encastrables',
					'Fours' => 'fours',
					'Friandises pour chat' => 'friandises-pour-chat',
					'Friandises pour chien' => 'friandises-pour-chien',
					'Friskies' => 'friskies',
					'Fruits & légumes' => 'fruits-et-legumes',
					'FURminator' => 'furminator',
					'Futuroscope' => 'futuroscope',
					'Gamelles' => 'gamelles',
					'Game of Thrones' => 'game-of-thrones',
					'Gants' => 'gants',
					'Gants moto' => 'gants-moto',
					'Garmin' => 'garmin',
					'Gâteaux & biscuits' => 'gateaux-et-biscuits',
					'Gels douche' => 'gels-douche',
					'Geox' => 'geox',
					'Gigoteuses' => 'gigoteuses',
					'Gillette' => 'gillette',
					'Glaces' => 'glaces',
					'God of War' => 'god-of-war',
					'Google Chromecast' => 'google-chromecast',
					'Google Home' => 'google-home',
					'Google Pixel 2' => 'google-pixel-2',
					'Google Pixel 2 XL' => 'google-pixel-2-xl',
					'Google Pixel' => 'google-pixel',
					'Google Pixel XL' => 'google-pixel-xl',
					'GoPro Hero' => 'gopro-hero',
					'Gran Turismo' => 'gran-turismo',
					'Gratuit' => 'gratuit',
					'Grille-pain' => 'grille-pain',
					'GTA' => 'gta',
					'GTA V' => 'gta-v',
					'Guitares' => 'guitares',
					'Gyropodes' => 'gyropodes',
					'Haltères & poids' => 'halteres-et-poids',
					'Hamacs' => 'hamacs',
					'Hama' => 'hama',
					'Hand spinners' => 'hand-spinners',
					'Harnais pour chien' => 'harnais-pour-chien',
					'Harry Potter' => 'harry-potter',
					'Havaianas' => 'havaianas',
					'HDD' => 'hdd',
					'Hisense' => 'hisense',
					'Home Cinéma' => 'home-cinema',
					'Honor 6X' => 'honor-6x',
					'Honor 8' => 'honor-8',
					'Honor 8 Pro' => 'honor-8-pro',
					'Honor 9' => 'honor-9',
					'Horizon Zero Dawn' => 'horizon-zero-dawn',
					'Hôtels' => 'hotels',
					'Hoverboards' => 'hoverboards',
					'HTC 10' => 'htc-10',
					'HTC Desire' => 'htc-desire',
					'HTC One M9' => 'htc-one-m9',
					'HTC U11' => 'htc-u11',
					'HTC U Play' => 'htc-u-play',
					'HTC U Ultra' => 'htc-u-ultra',
					'HTC Vive' => 'htc-vive',
					'Huawei Mate 10' => 'huawei-mate-10',
					'Huawei Mate 9' => 'huawei-mate-9',
					'Huawei P10' => 'huawei-p10',
					'Huawei P10 Lite' => 'huawei-p10-lite',
					'Huawei P10 Plus' => 'huawei-p10-plus',
					'Huawei P20' => 'huawei-p20',
					'Huawei P20 Pro' => 'huawei-p20-pro',
					'Huawei P8 Lite' => 'huawei-p8-lite',
					'Huawei P9 Lite' => 'huawei-p9-lite',
					'Hubs' => 'hubs',
					'Huile moteur' => 'huile-moteur',
					'Hygiène corporelle' => 'hygiene-corporelle',
					'Hygiène de la maison' => 'hygiene-de-la-maison',
					'Hygiène des bébés' => 'hygiene-des-bebes',
					'Image, son & vidéo' => 'image-son-video',
					'Impressions photo' => 'impressions-photo',
					'Imprimantes 3D' => 'imprimantes-3d',
					'Imprimantes Brother' => 'imprimantes-brother',
					'Imprimantes Canon' => 'imprimantes-canon',
					'Imprimantes Epson' => 'imprimantes-epson',
					'Imprimantes HP' => 'imprimantes-hp',
					'Imprimantes' => 'imprimantes',
					'Imprimantes laser' => 'imprimantes-laser',
					'Imprimantes multifonctions' => 'imprimantes-multifonctions',
					'Informatique' => 'informatique',
					'Instruments de musique' => 'instruments-de-musique',
					'Intel i5' => 'intel-i5',
					'Intel i7' => 'intel-i7',
					'JBL Flip' => 'jbl-flip',
					'JBL' => 'jbl',
					'Jeans' => 'jeans',
					'Jeux d&#039;apprentissage' => 'jeux-d-apprentissage',
					'Jeux d&#039;extérieur' => 'jeux-d-exterieur',
					'Jeux d&#039;imitation' => 'jeux-d-imitation',
					'Jeux de construction' => 'jeux-de-construction',
					'Jeux de société' => 'jeux-de-societe',
					'Jeux & jouets' => 'jeux-jouets',
					'Jeux Nintendo Switch' => 'jeux-nintendo-switch',
					'Jeux & paris' => 'jeux-et-paris',
					'Jeux PC dématérialisés' => 'jeux-pc-dematerialises',
					'Jeux PlayStation 4' => 'jeux-playstation-4',
					'Jeux pour bébés' => 'jeux-pour-bebes',
					'Jeux PS4 dématérialisés' => 'jeux-ps4-dematerialises',
					'Jeux PS Plus' => 'jeux-ps-plus',
					'Jeux vidéo' => 'jeux-video',
					'Jeux Wii U' => 'jeux-wii-u',
					'Jeux Xbox dématérialisés' => 'jeux-xbox-dematerialises',
					'Jeux Xbox One' => 'jeux-xbox-one',
					'Jeux Xbox with Gold' => 'jeux-xbox-with-gold',
					'Journaux numériques' => 'journaux-numeriques',
					'Journaux papier' => 'journaux-papier',
					'Joy-Con' => 'manettes-nintendo-switch-joy-con',
					'Jungle Speed' => 'jungle-speed',
					'Kaspersky' => 'kaspersky',
					'Kinder' => 'kinder',
					'Kindle Paperwhite' => 'kindle-paperwhite',
					'Kindle Voyage' => 'kindle-voyage',
					'Kobo Aura 2' => 'kobo-aura-2',
					'Kobo Aura H2o' => 'kobo-aura-h2o',
					'Kobo' => 'kobo',
					'L&#039;annale du destin' => 'l-annale-du-destin',
					'L&#039;ombre de la guerre' => 'l-ombre-de-la-guerre',
					'L&#039;ombre du Mordor' => 'l-ombre-du-mordor',
					'Lacoste' => 'lacoste',
					'Lapeyre' => 'lapeyre',
					'La Terre du Milieu' => 'la-terre-du-milieu',
					'Lavage auto' => 'lavage-auto',
					'Lave-linge frontal' => 'lave-linge-frontal',
					'Lave-linge' => 'lave-linge',
					'Lave-linge séchant' => 'lave-linge-sechant',
					'Lave-linge top' => 'lave-linge-top',
					'Lave-vaisselle' => 'lave-vaisselle',
					'Le bâton de la vérité' => 'le-baton-de-la-verite',
					'Lecteurs Blu-Ray' => 'lecteurs-blu-ray',
					'Lecteurs CD' => 'lecteurs-cd',
					'Lecteurs DVD' => 'lecteurs-dvd',
					'Lego' => 'lego',
					'Lego Star Wars' => 'lego-star-wars',
					'Lenovo K6 Note' => 'lenovo-k6-note',
					'Lenovo' => 'lenovo',
					'Lenovo P8' => 'lenovo-p8',
					'Lenovo Tab 3' => 'lenovo-tab-3',
					'Lenovo Tab 4' => 'lenovo-tab-4',
					'Lenovo Yoga' => 'lenovo-yoga',
					'Lenovo Yoga Tab 3' => 'lenovo-yoga-tab-3',
					'Lentilles de contact' => 'lentilles-de-contact',
					'Le Seigneur des anneaux' => 'le-seigneur-des-anneaux',
					'Les Sims' => 'les-sims',
					'Lessive' => 'lessive',
					'Levi&#039;s' => 'levi-s',
					'LG G4' => 'lg-g4',
					'LG G5' => 'lg-g5',
					'LG G6' => 'lg-g6',
					'LG' => 'lg',
					'LG OLED TV' => 'lg-oled-tv',
					'LG Q6' => 'lg-q6',
					'LG Q8' => 'lg-q8',
					'Life is Strange' => 'life-is-strange',
					'Linge de maison' => 'linge-de-maison',
					'Lingerie' => 'lingerie',
					'Lingettes pour bébés' => 'lingettes-pour-bebes',
					'Liseuses' => 'liseuses',
					'Litière pour chat' => 'litiere-pour-chat',
					'Lits' => 'lits',
					'Lits pour bébé' => 'lits-pour-bebe',
					'Livres audio' => 'livres-audio',
					'Livres' => 'livres',
					'Livres photo' => 'livres-photo',
					'Location de voiture' => 'location-de-voiture',
					'Logiciels de sécurité' => 'logiciels-de-securite',
					'Logiciels Microsoft' => 'logiciels-microsoft',
					'Logitech Harmony' => 'logitech-harmony',
					'Logitech' => 'logitech',
					'Loup-Garou' => 'loup-garou',
					'Lubrifiants' => 'lubrifiants',
					'Luminaires' => 'luminaires',
					'Lunettes de natation' => 'lunettes-de-natation',
					'Lunettes de soleil' => 'lunettes-de-soleil',
					'MacBook' => 'macbook',
					'Mac de bureau' => 'mac-de-bureau',
					'Machines à café à dosettes' => 'machines-a-cafe-a-dosettes',
					'Machines à café en grain' => 'machines-a-cafe-en-grain',
					'Machines à pain' => 'machines-a-pain',
					'Machines Dolce Gusto' => 'machines-dolce-gusto',
					'Machines Nespresso' => 'machines-nespresso',
					'Machines Senseo' => 'machines-senseo',
					'Magasins d&#039;usine' => 'magasins-usine',
					'Magazines' => 'magazines',
					'Maillots de bain' => 'maillots-de-bain',
					'Maillots de football' => 'maillots-de-football',
					'Maison & Jardin' => 'maison-et-jardin',
					'Makita' => 'makita',
					'Manettes Nintendo Switch Pro' => 'manettes-nintendo-switch-pro',
					'Manettes PlayStation 4' => 'manettes-playstation-4',
					'Manettes Xbox One Elite' => 'manettes-xbox-one-elite',
					'Manettes Xbox One' => 'manettes-xbox-one',
					'Manix' => 'manix',
					'Manteaux' => 'manteaux',
					'Maquillage' => 'maquillage',
					'Mario Kart' => 'mario-kart',
					'Marteaux & maillets' => 'marteaux-et-maillets',
					'Mascara' => 'mascara',
					'Masques de ski' => 'masques-de-ski',
					'Mass Effect: Andromeda' => 'mass-effect-andromeda',
					'Matchs de football' => 'matchs-de-football',
					'Matelas gonflables' => 'matelas-gonflables',
					'Matelas' => 'matelas',
					'Matériaux de construction' => 'materiaux-de-construction',
					'Matériel de ski' => 'materiel-de-ski',
					'Medion' => 'medion',
					'Meubles pour chat' => 'meubles-pour-chat',
					'Micro-casques gaming' => 'micro-casques-gaming',
					'Micro-ondes' => 'micro-ondes',
					'Microphones' => 'microphones',
					'Micro-SD' => 'micro-sd',
					'Microsoft Office' => 'microsoft-office',
					'Microsoft Surface' => 'microsoft-surface',
					'Miele' => 'miele',
					'Minecraft' => 'minecraft',
					'Mixeurs' => 'mixeurs',
					'M&M&#039;s' => 'metm-s',
					'Mobilier' => 'mobilier',
					'Mode & accessoires' => 'mode-accessoires',
					'Mode enfants' => 'mode-enfants',
					'Mode femme' => 'mode-femme',
					'Mode homme' => 'mode-homme',
					'Modélisme' => 'modelisme',
					'Monopoly' => 'monopoly',
					'Montage PC' => 'montage-pc',
					'Montres' => 'montres',
					'Moto C Plus' => 'moto-c-plus',
					'Moto E4' => 'moto-e4',
					'Moto G5' => 'moto-g5',
					'Moto G5 Plus' => 'moto-g5-plus',
					'Moto G5S' => 'moto-g5s',
					'Moto G5S Plus' => 'moto-g5s-plus',
					'Moto M' => 'moto-m',
					'Moto' => 'moto',
					'Moto Z2' => 'moto-z2',
					'Moto Z2 Play' => 'moto-z2-play',
					'Moulinex' => 'moulinex',
					'Mousses à raser' => 'mousses-a-raser',
					'MSI' => 'msi',
					'Musées' => 'musees',
					'Musique' => 'musique',
					'NAS' => 'nas',
					'Natation' => 'natation',
					'Navigation' => 'navigation',
					'NERF' => 'nerf',
					'New Balance' => 'new-balance',
					'Nike Air Force' => 'nike-air-force',
					'Nike Air Max' => 'nike-air-max',
					'Nike Free' => 'nike-free',
					'Nike Huarache' => 'nike-huarache',
					'Nike' => 'nike',
					'Nintendo Classic Mini' => 'nintendo-classic-mini',
					'Nintendo' => 'nintendo',
					'Nintendo Switch' => 'nintendo-switch',
					'Nivea' => 'nivea',
					'Nokia 5' => 'nokia-5',
					'Nokia 6' => 'nokia-6',
					'Nokia 8' => 'nokia-8',
					'Nourriture pour chat' => 'nourriture-pour-chat',
					'Nourriture pour chien' => 'nourriture-pour-chien',
					'Nutella' => 'nutella',
					'Nvidia GeForce GTX 1060' => 'nvidia-geforce-gtx-1060',
					'Nvidia GeForce GTX 1070' => 'nvidia-geforce-gtx-1070',
					'Nvidia GeForce GTX 1080' => 'nvidia-geforce-gtx-1080',
					'Nvidia GeForce GTX 1080 Ti' => 'nvidia-geforce-gtx-1080-ti',
					'Nvidia' => 'nvidia',
					'Nvidia Shield' => 'nvidia-shield',
					'Objectifs' => 'objectifs',
					'Oculus Rift' => 'oculus-rift',
					'Oiseaux' => 'oiseaux',
					'OnePlus 5' => 'oneplus-5',
					'OnePlus 5T' => 'oneplus-5t',
					'OnePlus 6' => 'oneplus-6',
					'Onkyo' => 'onkyo',
					'Ordinateurs de bureau' => 'ordinateurs-de-bureau',
					'Oreillers' => 'oreillers',
					'Outillage' => 'outillage',
					'Outils de jardinage' => 'outils-de-jardinage',
					'Overwatch' => 'overwatch',
					'Packs clavier-souris' => 'packs-clavier-souris',
					'Paiement en ligne' => 'paiement-en-ligne',
					'Pampers' => 'pampers',
					'Panasonic' => 'panasonic',
					'Panier Plus' => 'panier-plus',
					'Pantalons' => 'pantalons',
					'Papeterie' => 'papeterie',
					'Papier peint' => 'papier-peint',
					'Papier toilette' => 'papier-toilette',
					'Parapharmacie' => 'parapharmacie',
					'Parc Astérix' => 'parc-asterix',
					'Parfums femme' => 'parfums-femme',
					'Parfums homme' => 'parfums-homme',
					'Parfums' => 'parfums',
					'Parkas' => 'parkas',
					'Parrot' => 'parrot',
					'Partitions' => 'partitions',
					'PC de bureau complets' => 'pc-de-bureau-complets',
					'PC gamer complets' => 'pc-gamer-complets',
					'PC hybrides' => 'hybrides',
					'PC portables' => 'pc-portables',
					'Pêche' => 'peche',
					'Peintures' => 'peintures',
					'Peluches' => 'peluches',
					'Perceuses' => 'perceuses',
					'Périphériques PC' => 'peripheriques-pc',
					'Pèse-personnes' => 'pese-personnes',
					'PES' => 'pro-evolution-soccer',
					'Petites voitures' => 'petites-voitures',
					'Philips Hue' => 'philips-hue',
					'Philips Lumea' => 'philips-lumea',
					'Philips One Blade' => 'philips-one-blade',
					'Philips' => 'philips',
					'Philips Sonicare' => 'philips-sonicare',
					'Photo' => 'photo',
					'Pièces auto' => 'pieces-auto',
					'Pièces moto' => 'pieces-moto',
					'Pièces vélo' => 'pieces-velo',
					'Piles' => 'piles',
					'Piles rechargeables' => 'piles-rechargeables',
					'Pinces' => 'pinces',
					'Pizza' => 'pizza',
					'Places de cinéma' => 'places-de-cinema',
					'Plage' => 'plage',
					'Plantes' => 'plantes',
					'Plaques de cuisson' => 'plaques-de-cuisson',
					'Platines vinyle' => 'platines-vinyle',
					'Playmobil' => 'playmobil',
					'PlayStation 4' => 'playstation-4',
					'PlayStation 4 Pro' => 'playstation-4-pro',
					'PlayStation 4 Slim' => 'playstation-4-slim',
					'PlayStation' => 'playstation',
					'PlayStation Plus' => 'playstation-plus',
					'Playstation Store' => 'playstation-store',
					'Plomberie' => 'plomberie',
					'Pneus' => 'pneus',
					'PocketBook' => 'pocketbook',
					'Poêles' => 'poeles',
					'Pokémon' => 'pokemon',
					'Portables gamer' => 'portables-gamer',
					'Porte-bébé' => 'porte-bebe',
					'Portefeuilles' => 'portefeuilles',
					'Posters' => 'posters',
					'Potager' => 'potager',
					'Poulaillers' => 'poulaillers',
					'Poupées' => 'poupees',
					'Poussettes' => 'poussettes',
					'Premiers secours' => 'premiers-secours',
					'Préservatifs' => 'preservatifs',
					'Princesse Tam-Tam' => 'princesse-tam-tam',
					'Processeurs' => 'processeurs',
					'Protection de la maison' => 'protection-de-la-maison',
					'Protections intimes' => 'protections-intimes',
					'Puériculture' => 'puericulture',
					'Pulls' => 'pulls',
					'Puma' => 'puma',
					'Purificateurs d&#039;air' => 'purificateurs-d-air',
					'Purina' => 'purina',
					'Puzzles' => 'puzzles',
					'Pyjamas pour bébés' => 'pyjamas-pour-bebes',
					'Pyjamas' => 'pyjamas',
					'Qobuz' => 'qobuz',
					'RAM' => 'ram',
					'Randonnée' => 'randonnee',
					'Rasage' => 'rasage',
					'Rasoirs électriques' => 'rasoirs-electriques',
					'Rasoirs manuels' => 'rasoirs-manuels',
					'Raspberry Pi' => 'raspberry-pi',
					'Ray-Ban' => 'ray-ban',
					'Razer' => 'razer',
					'Réductions étudiants & jeunes' => 'reductions-etudiants-et-jeunes',
					'Reebok' => 'reebok',
					'Réfrigérateurs' => 'refrigerateurs',
					'Réhausseurs' => 'rehausseurs',
					'Remington' => 'remington',
					'Répéteurs' => 'repeteurs',
					'Réseau' => 'reseau',
					'Resident Evil 7' => 'resident-evil-7',
					'Resident Evil' => 'resident-evil',
					'Restaurants' => 'restaurants',
					'Richelieus' => 'richelieus',
					'Risk' => 'risk',
					'Rongeurs' => 'rongeurs',
					'Rouges à lèvres' => 'rouges-a-levres',
					'Routeurs' => 'routeurs',
					'Royal Canin' => 'royal-canin',
					'Running' => 'running',
					'Sacs à dos' => 'sacs-a-dos',
					'Sacs à langer' => 'sacs-a-langer',
					'Sacs à main' => 'sacs-a-main',
					'Samsonite' => 'samsonite',
					'Samsung Galaxy A5' => 'samsung-galaxy-a5',
					'Samsung Galaxy Note 8' => 'samsung-galaxy-note-8',
					'Samsung Galaxy S7 Edge' => 'samsung-galaxy-s7-edge',
					'Samsung Galaxy S7' => 'samsung-galaxy-s7',
					'Samsung Galaxy S8' => 'samsung-galaxy-s8',
					'Samsung Galaxy S8+' => 'samsung-galaxy-s8plus',
					'Samsung Galaxy S9' => 'samsung-galaxy-s9',
					'Samsung Galaxy Tab A' => 'samsung-galaxy-tab-a',
					'Samsung Galaxy Tab S2' => 'samsung-galaxy-tab-s2',
					'Samsung Galaxy Tab S3' => 'samsung-galaxy-tab-s3',
					'Samsung Gear' => 'samsung-gear',
					'Samsung Gear VR' => 'samsung-gear-vr',
					'Samsung' => 'samsung',
					'Sandales' => 'sandales',
					'SanDisk' => 'sandisk',
					'Santé & Cosmétiques' => 'sante-et-cosmetiques',
					'Savons' => 'savons',
					'Scanners' => 'scanners',
					'Scies' => 'scies',
					'Scooters' => 'scooters',
					'Seagate' => 'seagate',
					'Sécateurs' => 'secateurs',
					'Sèche-cheveux' => 'seche-cheveux',
					'Sèche-linge' => 'seche-linge',
					'Séjours' => 'sejours',
					'Sennheiser' => 'sennheiser',
					'Séries TV' => 'series-tv',
					'Services divers' => 'services-divers',
					'Serviettes hygiéniques' => 'serviettes-hygieniques',
					'Serviettes' => 'serviettes',
					'Sextoys' => 'sextoys',
					'Shorts de bain' => 'shorts-de-bain',
					'Shorts' => 'shorts',
					'Sièges auto' => 'sieges-auto',
					'Siemens' => 'siemens',
					'Skechers' => 'sketchers',
					'Ski' => 'ski',
					'Skyrim' => 'skyrim',
					'Smartbox' => 'smartbox',
					'Smart Home' => 'smart-home',
					'Smartphones à moins de 100€' => 'smartphones-moins-de-100',
					'Smartphones à moins de 200€' => 'smartphones-moins-de-200',
					'Smartphones Android' => 'smartphones-android',
					'Smartphones Huawei' => 'smartphones-huawei',
					'Smartphones Nokia' => 'smartphones-nokia',
					'Smartphones Samsung' => 'smartphones-samsung',
					'Smartphones' => 'smartphones',
					'Smartphones Xiaomi' => 'smartphones-xiaomi',
					'Smart TV' => 'smart-tv',
					'Smartwatch' => 'smartwatch',
					'Sneakers' => 'sneakers',
					'Soin des cheveux' => 'soin-des-cheveux',
					'Sonos PLAYBAR' => 'sonos-playbar',
					'Sonos' => 'sonos',
					'Sony PlayStation VR' => 'sony-playstation-vr',
					'Sony' => 'sony',
					'Sony Xperia XA1' => 'sony-xperia-xa1',
					'Sony Xperia X Compact' => 'sony-xperia-x-compact',
					'Sony Xperia XZ1 Compact' => 'sony-xperia-xz1-compact',
					'Sony Xperia XZ1' => 'sony-xperia-xz1',
					'Sony Xperia XZ Premium' => 'sony-xperia-xz-premium',
					'Sony Xperia Z3' => 'sony-xperia-z3',
					'Sorties' => 'sorties',
					'Souris gamer' => 'souris-gamer',
					'Souris Logitech' => 'souris-logitech',
					'Souris sans fil' => 'souris-sans-fil',
					'Souris' => 'souris',
					'South Park' => 'south-park',
					'Spectacles comiques' => 'spectacles-comiques',
					'Spectacles' => 'spectacles',
					'Sports & plein air' => 'sports-plein-air',
					'Spotify' => 'spotify',
					'SSD' => 'ssd',
					'Star Wars Battlefront' => 'star-wars-battlefront',
					'Stickers muraux' => 'stickers-muraux',
					'Stihl' => 'stihl',
					'Stockage externe' => 'stockage',
					'Streaming musical' => 'streaming-musical',
					'Stylos' => 'stylos',
					'Sucettes' => 'sucettes',
					'Super Mario' => 'super-mario',
					'Support GPS & smartphone' => 'support-gps-et-smartphone',
					'Surface Pro 4' => 'surface-pro-4',
					'Surgelés' => 'surgeles',
					'Surveillance' => 'surveillance',
					'Swatch' => 'swatch',
					'Switch réseau' => 'switch-reseau',
					'Systèmes d&#039;exploitation' => 'systemes-d-exploitation',
					'Systèmes multiroom' => 'systemes-multiroom',
					'Tables à langer' => 'tables-a-langer',
					'Tables de camping' => 'tables-de-camping',
					'Tables de mixage' => 'tables-de-mixage',
					'Tables' => 'tables',
					'Tablettes graphiques Huion' => 'huion',
					'Tablettes graphiques' => 'tablettes-graphiques',
					'Tablettes graphiques Wacom' => 'wacom',
					'Tablettes Lenovo' => 'tablettes-lenovo',
					'Tablettes Samsung' => 'tablettes-samsung',
					'Tablettes' => 'tablettes',
					'Tablettes Xiaomi' => 'tablettes-xiaomi',
					'Tampons' => 'tampons',
					'Tapis' => 'tapis',
					'Taxis' => 'taxis',
					'Tefal' => 'tefal',
					'Télécommandes' => 'telecommandes',
					'Téléphones fixes' => 'telephones-fixes',
					'Téléphonie' => 'telephonie',
					'Téléviseurs' => 'televiseurs',
					'Tentes' => 'tentes',
					'Têtes de brosse à dents de rechange' => 'tetes-de-brosse-a-dents-de-rechange',
					'Théâtre' => 'theatre',
					'The Legend of Zelda' => 'the-legend-of-zelda',
					'Thermomètres' => 'thermometres',
					'Thermomix' => 'thermomix',
					'Thés glacés' => 'thes-glaces',
					'Thés' => 'thes',
					'The Walking dead' => 'the-walking-dead',
					'The Witcher 3' => 'the-witcher-3',
					'The Witcher' => 'the-witcher',
					'Time&#039;s Up!' => 'time-s-up',
					'Tom Clancy&#039;s Ghost Recon: Wildlands' => 'tom-clancy-s-ghost-recon-wildlands',
					'Tom Clancy&#039;s The Division' => 'tom-clancy-s-the-division',
					'Tom Clancy&#039;s' => 'tom-clancy-s',
					'TomTom' => 'tomtom',
					'Tondeuses à gazon' => 'tondeuses-a-gazon',
					'Tondeuses' => 'tondeuses',
					'Toner' => 'toner',
					'Torchons' => 'torchons',
					'Toshiba' => 'toshiba',
					'Total War' => 'total-war',
					'Total War: Warhammer II' => 'total-war-warhammer-ii',
					'Total War: Warhammer' => 'total-war-warhammer',
					'Tournevis & visseuses' => 'tournevis-et-visseuses',
					'TP-Link' => 'tp-link',
					'Transats & cosys' => 'transats-et-cosys',
					'Transports en commun' => 'transports-en-commun',
					'Trixie' => 'trixie',
					'Tronçonneuses' => 'tronconneuses',
					'Trottinettes électriques' => 'trottinettes-electriques',
					'Trottinettes' => 'trottinettes',
					'T-shirts' => 't-shirts',
					'TV 39&#039;&#039; et moins' => 'tv-39-pouces-et-moins',
					'TV 40&#039;&#039; à 64&#039;&#039;' => 'tv-40-pouces-a-64-pouces',
					'TV 4K' => 'tv-4k',
					'TV 65&#039;&#039; et plus' => 'tv-65-pouces-et-plus',
					'TV Full HD' => 'tv-full-hd',
					'TV incurvées' => 'tv-incurvees',
					'TV LG' => 'tv-lg',
					'TV OLED' => 'tv-oled',
					'TV Panasonic' => 'tv-panasonic',
					'TV Philips' => 'tv-philips',
					'TV Samsung' => 'tv-samsung',
					'TV Sony' => 'tv-sony',
					'Ultraportables' => 'ultraportables',
					'Uncharted 4' => 'uncharted-4',
					'Uncharted: The Lost Legacy' => 'uncharted-the-lost-legacy',
					'Uncharted' => 'uncharted',
					'Ustensiles de cuisine' => 'ustensiles-de-cuisine',
					'Ustensiles de cuisson' => 'ustensiles-de-cuisson',
					'Vaisselle' => 'vaisselle',
					'Valises cabine' => 'valises-cabine',
					'Valises rigides' => 'valises-rigides',
					'Valises' => 'valises',
					'Variétés & revues' => 'varietes-et-revues',
					'Vases' => 'vases',
					'Veet' => 'veet',
					'Vélos d&#039;appartement' => 'velos-d-appartement',
					'Vélos' => 'velos',
					'Ventilateurs' => 'ventilateurs',
					'Ventirad' => 'ventirad',
					'Vernis à ongles' => 'vernis-a-ongles',
					'Vestes' => 'vestes',
					'Vêtements d&#039;été' => 'vetements-d-ete',
					'Vêtements d&#039;hiver' => 'vetements-d-hiver',
					'Vêtements de grossesse' => 'vetements-de-grossesse',
					'Vêtements de ski' => 'vetements-de-ski',
					'Vêtements de sport' => 'vetements-de-sport',
					'Vêtements pour bébé' => 'vetements-pour-bebe',
					'Vêtements techniques' => 'vetements-techniques',
					'Vidéoprojecteurs 3D' => 'videoprojecteurs-3d',
					'Vidéoprojecteurs Acer' => 'videoprojecteurs-acer',
					'Vidéoprojecteurs BenQ' => 'videoprojecteurs-benq',
					'Vidéoprojecteurs Epson' => 'videoprojecteurs-epson',
					'Vidéoprojecteurs HD' => 'videoprojecteurs-hd',
					'Vidéoprojecteurs LG' => 'videoprojecteurs-lg',
					'Vidéoprojecteurs Optoma' => 'videoprojecteurs-optoma',
					'Vidéoprojecteurs' => 'projecteurs',
					'Vidéo' => 'video',
					'Vins' => 'vins',
					'Visites & patrimoine' => 'visites-et-patrimoine',
					'VOD' => 'vod',
					'Voitures télécommandées' => 'voitures-telecommandees',
					'Voyages & sorties' => 'voyages-et-sorties',
					'Voyages' => 'voyages',
					'VPN' => 'vpn',
					'VR' => 'vr',
					'VTC' => 'vtc',
					'VTT' => 'vtt',
					'Wacom Cintiq' => 'cintiq',
					'Watercooling' => 'watercooling',
					'WD (Western Digital)' => 'western-digital',
					'Wearables' => 'wearables',
					'Whey' => 'whey',
					'Whirlpool' => 'whirlpool',
					'Whiskas' => 'whiskas',
					'Wii U' => 'wii-u',
					'Wiko' => 'wiko',
					'Windows' => 'windows',
					'WindScribe' => 'windscribe',
					'Wolfenstein II: The New Colossus' => 'wolfenstein-ii-the-new-colossus',
					'Wolfenstein' => 'wolfenstein',
					'Wonderbox' => 'wonderbox',
					'Xbox Live' => 'xbox-live',
					'Xbox One S' => 'xbox-one-s',
					'Xbox One' => 'xbox-one',
					'Xbox One X' => 'xbox-one-x',
					'Xbox' => 'xbox',
					'Xiaomi Mi6' => 'xiaomi-mi6',
					'Xiaomi Mi A1' => 'xiaomi-mi-a1',
					'Xiaomi Mi Band' => 'xiaomi-mi-band',
					'Xiaomi Mi Box' => 'xiaomi-mi-box',
					'Xiaomi Mi Max' => 'xiaomi-mi-max',
					'Xiaomi Mi Mix 2' => 'xiaomi-mi-mix-2',
					'Xiaomi Mi Mix' => 'xiaomi-mi-mix',
					'Xiaomi Mi Pad 3' => 'xiaomi-mi-pad-3',
					'Xiaomi Redmi 4A' => 'xiaomi-redmi-4a',
					'Xiaomi Redmi 4X' => 'xiaomi-redmi-4x',
					'Xiaomi Redmi Note 4' => 'xiaomi-redmi-note-4',
					'Xiaomi Smart Home' => 'xiaomi-smart-home',
					'Xiaomi' => 'xiaomi',
					'Yamaha' => 'yamaha',
					'Zelda: Breath of the Wild' => 'zelda-breath-of-the-wild',
					'Zoos' => 'zoos',
				)
			),
			'order' => array(
				'name' => 'Trier par',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Ordre de tri des deals',
				'values' => array(
					'Du deal le plus Hot au moins Hot' => '',
					'Du deal le plus récent au plus ancien' => '-nouveaux',
					'Du deal le plus commentés au moins commentés' => '-commentes'
				)
			)
		)
	);

	public $lang = array(
		'bridge-uri' => SELF::URI,
		'bridge-name' => SELF::NAME,
		'context-keyword' => 'Recherche par Mot(s) clé(s)',
		'context-group' => 'Deals par groupe',
		'uri-group' => '/groupe/',
		'request-error' => 'Could not request Dealabs',
		'no-results' => 'Il n&#039;y a rien à afficher pour le moment :(',
		'relative-date-indicator' => array(
			'il y a',
		),
		'price' => 'Prix',
		'shipping' => 'Livraison',
		'origin' => 'Origine',
		'discount' => 'Réduction',
		'title-keyword' => 'Recherche',
		'title-group' => 'Groupe',
		'local-months' => array(
			'janvier',
			'février',
			'mars',
			'avril',
			'mai',
			'juin',
			'juillet',
			'août',
			'septembre',
			'octobre',
			'novembre',
			'décembre'
		),
		'local-time-relative' => array(
			'il y a ',
			'min',
			'h',
			'jour',
			'jours',
			'mois',
			'ans',
			'et '
		),
		'date-prefixes' => array(
			'Actualisé ',
		),
		'relative-date-alt-prefixes' => array(
			'Actualisé ',
		),
		'relative-date-ignore-suffix' => array(
		),

		'localdeal' => array(
			'Local',
			'Pays d\'expédition'
		),
	);



}

class PepperBridgeAbstract extends BridgeAbstract {

	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		switch($this->queriedContext) {
		case $this->i8n('context-keyword'):
			return $this->collectDataKeywords();
			break;
		case $this->i8n('context-group'):
			return $this->collectDataGroup();
			break;
		}
	}

	/**
	 * Get the Deal data from the choosen group in the choosed order
	 */
	protected function collectDataGroup()
	{

		$group = $this->getInput('group');
		$order = $this->getInput('order');

		$url = $this->i8n('bridge-uri')
			. $this->i8n('uri-group') . $group . $order;
		$this->collectDeals($url);
	}

	/**
	 * Get the Deal data from the choosen keywords and parameters
	 */
	protected function collectDataKeywords()
	{
		$q = $this->getInput('q');
		$hide_expired = $this->getInput('hide_expired');
		$hide_local = $this->getInput('hide_local');
		$priceFrom = $this->getInput('priceFrom');
		$priceTo = $this->getInput('priceFrom');

		/* Even if the original website uses POST with the search page, GET works too */
		$url = $this->i8n('bridge-uri')
			. '/search/advanced?q='
			. urlencode($q)
			. '&hide_expired='. $hide_expired
			. '&hide_local='. $hide_local
			. '&priceFrom='. $priceFrom
			. '&priceTo='. $priceTo
			/* Some default parameters
			 * search_fields : Search in Titres & Descriptions & Codes
			 * sort_by : Sort the search by new deals
			 * time_frame : Search will not be on a limited timeframe
			 */
			. '&search_fields[]=1&search_fields[]=2&search_fields[]=3&sort_by=new&time_frame=0';
		$this->collectDeals($url);
	}

	/**
	 * Get the Deal data using the given URL
	 */
	protected function collectDeals($url){
		$html = getSimpleHTMLDOM($url)
			or returnServerError($this->i8n('request-error'));
		$list = $html->find('article[id]');

		// Deal Image Link CSS Selector
		$selectorImageLink = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-thread-image-link',
				'imgFrame',
				'imgFrame--noBorder',
				'thread-listImgCell',
			)
		);

		// Deal Link CSS Selector
		$selectorLink = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-tt',
				'thread-link',
				'linkPlain',
			)
		);

		// Deal Hotness CSS Selector
		$selectorHot = implode(
			' ', /* Notice this is a space! */
			array(
				'flex',
				'flex--align-c',
				'flex--justify-space-between',
				'space--b-2',
			)
		);

		// Deal Description CSS Selector
		$selectorDescription = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-description-container',
				'overflow--wrap-break'
			)
		);

		// Deal Date CSS Selector
		$selectorDate = implode(
			' ', /* Notice this is a space! */
			array(
				'size--all-s',
				'flex',
				'flex--justify-e',
				'flex--grow-1',
			)
		);

		// If there is no results, we don't parse the content because it display some random deals
		$noresult = $html->find('h3[class=size--all-l size--fromW2-xl size--fromW3-xxl]', 0);
		if ($noresult != null && strpos($noresult->plaintext, $this->i8n('no-results')) !== false) {
			$this->items = array();
		} else {
			foreach ($list as $deal) {
				$item = array();
				$item['uri'] = $deal->find('div[class=threadGrid-title]', 0)->find('a', 0)->href;
				$item['title'] = $deal->find('a[class*='. $selectorLink .']', 0
				)->plaintext;
				$item['author'] = $deal->find('span.thread-username', 0)->plaintext;
				$item['content'] = '<table><tr><td><a href="'
					. $deal->find(
						'a[class*='. $selectorImageLink .']', 0)->href
						. '"><img src="'
						. $this->getImage($deal)
						. '"/></td><td><h2><a href="'
						. $deal->find('a[class*='. $selectorLink .']', 0)->href
						. '">'
						. $deal->find('a[class*='. $selectorLink .']', 0)->innertext
						. '</a></h2>'
						. $this->getPrice($deal)
						. $this->getDiscount($deal)
						. $this->getShipsFrom($deal)
						. $this->getShippingCost($deal)
						. $this->GetSource($deal)
						. $deal->find('div[class*='. $selectorDescription .']', 0)->innertext
						. '</td><td>'
						. $deal->find('div[class='. $selectorHot .']', 0)->children(0)->outertext
						. '</td></table>';
				$dealDateDiv = $deal->find('div[class*='. $selectorDate .']', 0)
					->find('span[class=hide--toW3]');
				$itemDate = end($dealDateDiv)->plaintext;
				// In case of a Local deal, there is no date, but we can use
				// this case for other reason (like date not in the last field)
				if ($this->contains($itemDate, $this->i8n('localdeal'))) {
					$item['timestamp'] = time();
				} else if ($this->contains($itemDate, $this->i8n('relative-date-indicator'))) {
					$item['timestamp'] = $this->relativeDateToTimestamp($itemDate);
				} else {
					$item['timestamp'] = $this->parseDate($itemDate);
				}
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Check if the string $str contains any of the string of the array $arr
	 * @return boolean true if the string matched anything otherwise false
	 */
	private function contains($str, array $arr)
	{
		foreach ($arr as $a) {
			if (stripos($str, $a) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the Price from a Deal if it exists
	 * @return string String of the deal price
	 */
	private function getPrice($deal)
	{
		if ($deal->find(
			'span[class*=thread-price]', 0) != null) {
			return '<div>'.$this->i8n('price') .' : '
				. $deal->find(
					'span[class*=thread-price]', 0
				)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}


	/**
	 * Get the Shipping costs from a Deal if it exists
	 * @return string String of the deal shipping Cost
	 */
	private function getShippingCost($deal)
	{
		if ($deal->find('span[class*=cept-shipping-price]', 0) != null) {
			if ($deal->find('span[class*=cept-shipping-price]', 0)->children(0) != null) {
				return '<div>'. $this->i8n('shipping') .' : '
					. $deal->find('span[class*=cept-shipping-price]', 0)->children(0)->innertext
					. '</div>';
			} else {
				return '<div>'. $this->i8n('shipping') .' : '
					. $deal->find('span[class*=cept-shipping-price]', 0)->innertext
					. '</div>';
			}
		} else {
			return '';
		}
	}

	/**
	 * Get the source of a Deal if it exists
	 * @return string String of the deal source
	 */
	private function GetSource($deal)
	{
		if ($deal->find('a[class=text--color-greyShade]', 0) != null) {
			return '<div>'. $this->i8n('origin') .' : '
				. $deal->find('a[class=text--color-greyShade]', 0)->outertext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Get the original Price and discout from a Deal if it exists
	 * @return string String of the deal original price and discount
	 */
	private function getDiscount($deal)
	{
		if ($deal->find('span[class*=mute--text text--lineThrough]', 0) != null) {
			$discountHtml = $deal->find('span[class=space--ml-1 size--all-l size--fromW3-xl]', 0);
			if ($discountHtml != null) {
				$discount = $discountHtml->plaintext;
			} else {
				$discount = '';
			}
			return '<div>'. $this->i8n('discount') .' : <span style="text-decoration: line-through;">'
				. $deal->find(
					'span[class*=mute--text text--lineThrough]', 0
				)->plaintext
				. '</span>&nbsp;'
				. $discount
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Get the Picture URL from a Deal if it exists
	 * @return string String of the deal Picture URL
	 */
	private function getImage($deal)
	{
		$selectorLazy = implode(
			' ', /* Notice this is a space! */
			array(
				'thread-image',
				'width--all-auto',
				'height--all-auto',
				'imgFrame-img',
				'cept-thread-img',
				'img--dummy',
				'js-lazy-img'
			)
		);

		$selectorPlain = implode(
			' ', /* Notice this is a space! */
			array(
				'thread-image',
				'width--all-auto',
				'height--all-auto',
				'imgFrame-img',
				'cept-thread-img'
			)
		);
		if ($deal->find('img[class='. $selectorLazy .']', 0) != null) {
			return json_decode(
				html_entity_decode(
					$deal->find('img[class='. $selectorLazy .']', 0)
					->getAttribute('data-lazy-img')))->{'src'};
		} else {
			return $deal->find('img[class*='. $selectorPlain .']', 0	)->src;
		}
	}

	/**
	 * Get the originating country from a Deal if it exists
	 * @return string String of the deal originating country
	 */
	private function getShipsFrom($deal)
	{
		$selector = implode(
			' ', /* Notice this is a space! */
			array(
				'meta-ribbon',
				'overflow--wrap-off',
				'space--l-3',
				'text--color-greyShade'
			)
		);
		if ($deal->find('span[class='. $selector .']', 0) != null) {
			return '<div>'
				. $deal->find('span[class='. $selector .']', 0)->children(2)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Transforms a local date into a timestamp
	 * @return int timestamp of the input date
	 */
	private function parseDate($string)
	{
		$month_local = $this->i8n('local-months');
		$month_en = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'
		);

		// A date can be prfixed with some words, we remove theme
		$string = $this->removeDatePrefixes($string);
		// We translate the local months name in the english one
		$date_str = trim(str_replace($month_local, $month_en, $string));

		// If the date does not contain any year, we add the current year
		if (!preg_match('/[0-9]{4}/', $string)) {
			$date_str .= ' ' . date('Y');
		}

		// Add the Hour and minutes
		$date_str .= ' 00:00';

		$date = DateTime::createFromFormat('j F Y H:i', $date_str);
		return $date->getTimestamp();
	}

	/**
	 * Remove the prefix of a date if it has one
	 * @return the date without prefiux
	 */
	private function removeDatePrefixes($string)
	{
		$string = str_replace($this->i8n('date-prefixes'), array(), $string);
		return $string;
	}

	/**
	 * Remove the suffix of a relative date if it has one
	 * @return the relative date without suffixes
	 */
	private function removeRelativeDateSuffixes($string)
	{
		if (count($this->i8n('relative-date-ignore-suffix')) > 0) {
			$string = preg_replace($this->i8n('relative-date-ignore-suffix'), '', $string);
		}
		return $string;
	}

	/**
	 * Transforms a relative local date into a timestamp
	 * @return int timestamp of the input date
	 */
	private function relativeDateToTimestamp($str) {
		$date = new DateTime();

		// In case of update date, replace it by the regular relative date first word
		$str = str_replace($this->i8n('relative-date-alt-prefixes'), $this->i8n('local-time-relative')[0], $str);

		$str = $this->removeRelativeDateSuffixes($str);

		$search = $this->i8n('local-time-relative');

		$replace = array(
			'-',
			'minute',
			'hour',
			'day',
			'month',
			'year',
			''
		);

		$date->modify(str_replace($search, $replace, $str));
		return $date->getTimestamp();
	}

	/**
	 * Returns the RSS Feed title according to the parameters
	 * @return string the RSS feed Tiyle
	 */
	public function getName(){
		switch($this->queriedContext) {
			case $this->i8n('context-keyword'):
				return $this->i8n('bridge-name') . ' - '. $this->i8n('title-keyword') .' : '. $this->getInput('q');
				break;
			case $this->i8n('context-group'):
				$values = $this->getParameters()[$this->i8n('context-group')]['group']['values'];
				$group = array_search($this->getInput('group'), $values);
				return $this->i8n('bridge-name') . ' - '. $this->i8n('title-group'). ' : '. $group;
				break;
			default: // Return default value
				return static::NAME;
		}
	}



	/**
	 * This is some "localisation" function that returns the needed content using
	 * the "$lang" class variable in the local class
	 * @return various the local content needed
	 */
	protected function i8n($key)
	{
		if (array_key_exists($key, $this->lang)) {
			return $this->lang[$key];
		} else {
			return null;
		}
	}

}
