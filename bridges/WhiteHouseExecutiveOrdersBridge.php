<?php

require_once __DIR__ . '/../lib/WhiteHouseBridge.php';

class WhiteHouseExecutiveOrdersBridge extends WhiteHouseBridge
{
    const MAINTAINER = 'sij-ai';
    const NAME = 'White House Executive Orders';
    const URI = 'https://www.whitehouse.gov/presidential-actions/executive-orders/';
    const DESCRIPTION = 'Returns Executive Orders from The White House.';
    const CACHE_TIMEOUT = 7200; // 2 hours
}
