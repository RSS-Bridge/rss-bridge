<?php

require_once __DIR__ . '/lib/bootstrap.php';

$rssBridge = new RssBridge();

$rssBridge->main($argv ?? []);
