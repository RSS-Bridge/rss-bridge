<?php

require_once __DIR__ . '/../lib/WhiteHouseBridge.php';

class WhiteHouseMemorandaBridge extends WhiteHouseBridge
{
    const MAINTAINER = 'sij-ai';
    const NAME = 'White House Presidential Memoranda';
    const URI = 'https://www.whitehouse.gov/presidential-actions/presidential-memoranda/';
    const DESCRIPTION = 'Returns Presidential Memoranda from The White House.';
    const CACHE_TIMEOUT = 7200; // 2 hours
}
