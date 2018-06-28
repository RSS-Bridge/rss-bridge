<?php
/*
 * For users who do NOT want to install composer.
 * This file should take care of loading the right class.
 */
spl_autoload_register(function ($class) {
    // Not interested in class if its not a child of CloudflareBypass
    if (substr($class, 0, 17) !== 'CloudflareBypass\\') {
      return;
    }
    
    $class = str_replace('\\', '/', $class);
    
    $path = dirname(__FILE__).'/'.$class.'.php';
    if (is_readable($path)) {
        require_once $path;
    }
});