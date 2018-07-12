<?php

require_once(__DIR__ . '/DealabsBridge.php');
class MydealsBridge extends PepperBridgeAbstract {

	const NAME = 'Mydeals bridge';
	const URI = 'https://www.mydealz.de/';
	const DESCRIPTION = 'Zeigt die Deals von mydeals.de';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Suche nach Stichworten' => array (
			'q' => array(
				'name' => 'Stichworten',
				'type' => 'text',
				'required' => true
			),
			'hide_expired' => array(
				'name' => 'Abgelaufenes ausblenden',
				'type' => 'checkbox',
				'required' => 'true'
			),
			'hide_local' => array(
				'name' => 'Lokales ausblenden',
				'type' => 'checkbox',
				'title' => 'Deals im physischen Geschäft ausblenden',
				'required' => 'true'
			),
			'priceFrom' => array(
				'name' => 'Minimaler Preis',
				'type' => 'text',
				'title' => 'Minmaler Preis in Euros',
				'required' => 'false',
				'defaultValue' => ''
			),
			'priceTo' => array(
				'name' => 'Maximaler Preis',
				'type' => 'text',
				'title' => 'maximaler Preis in Euro',
				'required' => 'false',
				'defaultValue' => ''
			),
		),

		'Deals pro Gruppen' => array(
			'group' => array(
				'name' => 'Gruppen',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Gruppe, deren Deals angezeigt werden müssen',
				'values' => array(
					'Elektronik' => 'elektronik',
					'Handy & Smartphone' => 'smartphone',
					'Gaming' => 'gaming',
					'Software' => 'apps-software',
					'Fashion Frauen' => 'fashion-frauen',
					'Fashion Männer' => 'fashion-accessoires',
					'Beauty & Gesundheit' => 'beauty',
					'Family & Kids' => 'family-kids',
					'Essen & Trinken' => 'food',
					'Freizeit & Reisen' => 'reisen',
					'Haushalt & Garten' => 'home-living',
					'Entertainment' => 'entertainment',
					'Verträge & Finanzen' => 'vertraege-finanzen',
					'Coupons' => 'coupons',

				)
			),
			'order' => array(
				'name' => 'sortieren nach',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Sortierung der deals',
				'values' => array(
					'Vom heißesten zum kältesten Deal' => '',
					'Vom jüngsten Deal zum ältesten' => '-new',
					'Vom am meisten kommentierten Deal zum am wenigsten kommentierten Deal' => '-discussed'
				)
			)
		)
	);

	public $lang = array(
		'bridge-uri' => SELF::URI,
		'bridge-name' => SELF::NAME,
		'context-keyword' => 'Suche nach Stichworten',
		'context-group' => 'Deals pro Gruppen',
		'uri-group' => '/gruppe/',
		'request-error' => 'Could not request mydeals',
		'no-results' => 'Ups, wir konnten keine Deals zu',
		'relative-date-indicator' => array(
			'vor',
			'seit'
		),
		'price' => 'Preis',
		'shipping' => 'Versand',
		'origin' => 'Ursprung',
		'discount' => 'Rabatte',
		'title-keyword' => 'Suche',
		'title-group' => 'Gruppe',
		'local-months' => array(
			'Jan',
			'Feb',
			'Mär',
			'Apr',
			'Mai',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Okt',
			'Nov',
			'Dez',
			'.'
		),
		'local-time-relative' => array(
			'eingestellt vor ',
			'm',
			'h,',
			'day',
			'days',
			'month',
			'year',
			'and '
		),
		'date-prefixes' => array(
			'eingestellt am ',
			'lokal ',
			'aktualisiert ',
		),
		'relative-date-alt-prefixes' => array(
			'aktualisiert vor ',
			'kommentiert vor ',
			'heiß seit '
		),
		'relative-date-ignore-suffix' => array(
			'/von.*$/'
		),
		'localdeal' => array(
			'Lokal ',
			'Läuft bis '
		)
	);

}
