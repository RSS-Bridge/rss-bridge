<?php

require_once __DIR__ . '/../../lib/Configuration.php';
require_once __DIR__ . '/../../bridges/YoutubeBridge.php';

use PHPUnit\Framework\TestCase;

//\Configuration::verifyInstallation();
// \Configuration::loadConfiguration();

class YoutubeBridgeTest extends TestCase
{
	public function testDetectParameters() {
		$b = new \YoutubeBridge();
		$this->assertEqual(
			$b->detectParameters('https://www.youtube.com/playlist?list=PL0lo9MOBetEFEzIm3OP9_5jtkilBobdKB'),
			array('p' => 'PL0lo9MOBetEFEzIm3OP9_5jtkilBobdKB')
		);
	}
}
