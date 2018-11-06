<?php
/* rss-bridge library.
Foundation functions for rss-bridge project.
See https://github.com/sebsauvage/rss-bridge
Licence: Public domain.
*/

define('PATH_VENDOR', __DIR__ . '/../vendor');
define('PATH_LIB', __DIR__ . '/../lib');

require_once PATH_LIB . '/Exceptions.php';
require_once PATH_LIB . '/Format.php';
require_once PATH_LIB . '/FormatAbstract.php';
require_once PATH_LIB . '/Bridge.php';
require_once PATH_LIB . '/BridgeAbstract.php';
require_once PATH_LIB . '/FeedExpander.php';
require_once PATH_LIB . '/Cache.php';
require_once PATH_LIB . '/Authentication.php';
require_once PATH_LIB . '/Configuration.php';
require_once PATH_LIB . '/BridgeCard.php';
require_once PATH_LIB . '/BridgeList.php';
require_once PATH_LIB . '/ParameterValidator.php';

require_once PATH_LIB . '/html.php';
require_once PATH_LIB . '/error.php';
require_once PATH_LIB . '/contents.php';

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
