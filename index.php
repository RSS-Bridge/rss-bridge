<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    exit('RSS-Bridge requires minimum PHP version 7.4.0!');
}

require_once __DIR__ . '/lib/bootstrap.php';

$rssBridge = new RssBridge();

$rssBridge->main($argv ?? []);
