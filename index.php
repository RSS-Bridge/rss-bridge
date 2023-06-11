<?php

# This file is kept for legacy reasons in order to not break existing installations.
# If you have access to nginx config you should instead use the public folder as document root.

require_once __DIR__ . '/lib/bootstrap.php';

$rssBridge = new RssBridge();

$rssBridge->main($argv ?? []);
