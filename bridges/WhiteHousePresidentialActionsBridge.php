<?php
require_once 'WhiteHouseBridge.php';

class WhiteHousePresidentialActionsBridge extends WhiteHouseBridge
{
    const MAINTAINER = 'sij-ai';
    const NAME = 'White House Presidential Actions (All)';
    const URI = 'https://www.whitehouse.gov/presidential-actions/';
    const DESCRIPTION = 'Returns all Presidential Actions from The White House.';
    const CACHE_TIMEOUT = 3600; // 1 hour
}
