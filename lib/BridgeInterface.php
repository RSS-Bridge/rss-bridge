<?php
interface BridgeInterface {
    public function collectData();
    public function getCacheDuration();
    public function getName();
    public function getURI();
}
