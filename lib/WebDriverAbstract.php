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

    private $feedIcon;

    protected function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }

    public function getIcon()
    {
        return $this->feedIcon ?: parent::getIcon();
    }

    protected function setIcon($iconurl)
    {
        $this->feedIcon = $iconurl;
    }

    protected function getBrowserOptions()
    {
        $chromeOptions = new ChromeOptions();
        if (Configuration::getConfig('webdriver', 'headless')) {
            $chromeOptions->addArguments(['--headless']);   // --window-size=1024,1024
        }
        return $chromeOptions;
    }

    protected function getDesiredCapabilities(): WebDriverCapabilities
    {
        $desiredCapabilities = DesiredCapabilities::chrome();
        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $this->getBrowserOptions());
        return $desiredCapabilities;
    }

    protected function prepareWebDriver()
    {
        $server = Configuration::getConfig('webdriver', 'selenium_server_url');
        $this->driver = RemoteWebDriver::create($server, $this->getDesiredCapabilities());
    }

    protected function prepareWindow()
    {
        $this->getDriver()->manage()->window()->maximize();
        $this->getDriver()->get($this->getURI());
    }

    protected function cleanUp()
    {
        $this->getDriver()->quit();
    }

    public function collectData()
    {
        $this->prepareWebDriver();
        $this->prepareWindow();
    }
}