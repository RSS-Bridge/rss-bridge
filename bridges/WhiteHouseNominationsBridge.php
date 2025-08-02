<?php

require_once __DIR__ . '/../lib/WhiteHouseBridge.php';

class WhiteHouseNominationsBridge extends WhiteHouseBridge
{
    const MAINTAINER = 'sij-ai';
    const NAME = 'White House Nominations & Appointments';
    const URI = 'https://www.whitehouse.gov/presidential-actions/nominations-appointments/';
    const DESCRIPTION = 'Returns Nominations & Appointments from The White House.';
    const CACHE_TIMEOUT = 7200; // 2 hours
}
