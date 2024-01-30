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
        $chromeOptions = new ChromeOptions();
        if (Configuration::getConfig('webdriver', 'headless')) {
            $chromeOptions->addArguments(['--headless']);
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
        $this->getDriver()->manage()->window()->maximize(); // TODO? 1024 x 1024
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