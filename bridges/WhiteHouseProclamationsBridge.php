<?php

require_once __DIR__ . '/../lib/WhiteHouseBridge.php';

class WhiteHouseProclamationsBridge extends WhiteHouseBridge
{
    const MAINTAINER = 'sij-ai';
    const NAME = 'White House Proclamations';
    const URI = 'https://www.whitehouse.gov/presidential-actions/proclamations/';
    const DESCRIPTION = 'Returns Proclamations from The White House.';
    const CACHE_TIMEOUT = 7200; // 2 hours
}
