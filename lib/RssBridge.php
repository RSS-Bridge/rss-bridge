<?php
/* rss-bridge library.
Foundation functions for rss-bridge project.
See https://github.com/sebsauvage/rss-bridge
Licence: Public domain.
*/

define('PATH_VENDOR', __DIR__ . '/../vendor');

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

require_once PATH_VENDOR . '/simplehtmldom/simple_html_dom.php';
require_once PATH_VENDOR . '/php-urljoin/src/urljoin.php';

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
