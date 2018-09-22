<?php
/* rss-bridge library.
Foundation functions for rss-bridge project.
See https://github.com/sebsauvage/rss-bridge
Licence: Public domain.
*/

define('PATH_VENDOR', '/../vendor');

require __DIR__ . '/Exceptions.php';
require __DIR__ . '/Format.php';
require __DIR__ . '/FormatAbstract.php';
require __DIR__ . '/Bridge.php';
require __DIR__ . '/BridgeAbstract.php';
require __DIR__ . '/FeedExpander.php';
require __DIR__ . '/Cache.php';
require __DIR__ . '/Authentication.php';
require __DIR__ . '/Configuration.php';
require __DIR__ . '/BridgeCard.php';
require __DIR__ . '/BridgeList.php';
require __DIR__ . '/ParameterValidator.php';

require __DIR__ . '/html.php';
require __DIR__ . '/error.php';
require __DIR__ . '/contents.php';

$vendorLibSimpleHtmlDom = __DIR__ . PATH_VENDOR . '/simplehtmldom/simple_html_dom.php';
if(!file_exists($vendorLibSimpleHtmlDom)) {
	throw new \HttpException('"PHP Simple HTML DOM Parser" library is missing.
 Get it from http://simplehtmldom.sourceforge.net and place the script "simple_html_dom.php" in '
		. substr(PATH_VENDOR, 4)
		. '/simplehtmldom/',
	500);
}
require_once $vendorLibSimpleHtmlDom;

$vendorLibPhpUrlJoin = __DIR__ . PATH_VENDOR . '/php-urljoin/src/urljoin.php';
if(!file_exists($vendorLibPhpUrlJoin)) {
	throw new \HttpException('"php-urljoin" library is missing.
 Get it from https://github.com/fluffy-critter/php-urljoin and place the script "urljoin.php" in '
		. substr(PATH_VENDOR, 4)
		. '/php-urljoin/src/',
	500);
}
require_once $vendorLibPhpUrlJoin;


/* Example use

	require_once __DIR__ . '/lib/RssBridge.php';

	// Data retrieval
	Bridge::setDir(__DIR__ . '/bridges/');
	$bridge = Bridge::create('GoogleSearch');
	$bridge->collectData($_REQUEST);

	// Data transformation
	Format::setDir(__DIR__ . '/formats/');
	$format = Format::create('Atom');
	$format
		->setItems($bridge->getItems())
		->setExtraInfos(array(
			'name' => $bridge->getName(),
			'uri' => $bridge->getURI(),
			'icon' => $bridge->getIcon(),
		))
		->display();

*/
