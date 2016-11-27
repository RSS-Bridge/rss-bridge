<?php
require_once('GelbooruBridge.php');

class XbooruBridge extends GelbooruBridge{

    const MAINTAINER = "mitsukarenai";
    const NAME = "Xbooru";
    const URI = "http://xbooru.com/";
    const DESCRIPTION = "Returns images from given page";

    const PIDBYPAGE=50;
}
