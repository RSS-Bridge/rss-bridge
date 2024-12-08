<?php

// This bridge depends on Releases3DSBridge
if (!class_exists('Releases3DSBridge')) {
    include('Releases3DSBridge.php');
}

class ReleasesSwitchBridge extends Releases3DSBridge
{
    const NAME = 'Switch Scene Releases';
    const URI = 'http://nswdb.com/';
    const DESCRIPTION = 'Returns the newest scene releases for Nintendo Switch.';

    public function collectData()
    {
        $this->collectDataUrl(self::URI . 'xml.php');
    }
}
