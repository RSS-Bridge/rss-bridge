<?php

require_once __DIR__ . '/lib/rssbridge.php';

$rssBridge = new RssBridge();

$rssBridge->main($argv ?? []);
