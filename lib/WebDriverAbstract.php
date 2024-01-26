<?php

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverCapabilities;

/**
 * An alternative abstract class for bridges depending on webdriver
 *
 * This class is meant a solution for active websites that use AJAX to load
 * their content. This class depends on a working webdriver setup.
 */
abstract class WebDriverAbstract extends BridgeAbstract
{
    protected RemoteWebDriver $driver;

    public function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }

    protected function getBrowserOptions()
    {
        return new ChromeOptions();
    }

    protected function getDesiredCapabilities(): WebDriverCapabilities
    {
        $desiredCapabilities = DesiredCapabilities::chrome();
        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $this->getBrowserOptions());
        return $desiredCapabilities;
    }

    protected function prepareWebDriver()   // TODO move to __construct()
    {
        // TODO try catch WebDriverCurlException -> Couldn't connect to server
        $this->driver = RemoteWebDriver::create(Configuration::getConfig('webdriver', 'selenium_server_url'), $this->getDesiredCapabilities());
        $this->driver->manage()->window()->maximize();
    }

    public function collectData()
    {
        $this->prepareWebDriver();
        $this->driver->get($this->getURI());
    }
}