<?php

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverCapabilities;

/**
 * An alternative abstract class for bridges depending on webdriver
 *
 * This class is meant a solution for active websites that use
 * XMLHttpRequest (XHR) to load content and/or use JavaScript to
 * change content. This class depends on a working webdriver setup.
 */
abstract class WebDriverAbstract extends BridgeAbstract
{
    /**
     * Holds the remote webdriver object, including configuration and
     * connection.
     *
     * @var RemoteWebDriver
     */
    protected RemoteWebDriver $driver;

    /**
     * Holds the uri of the feed's icon.
     *
     * @var string | null
     */
    private $feedIcon;

    /**
     * Returns the webdriver object.
     *
     * @return RemoteWebDriver
     */
    protected function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }

    /**
     * Returns the uri of the feed's icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->feedIcon ?: parent::getIcon();
    }

    /**
     * Sets the uri of the feed's icon.
     *
     * @param $iconurl string
     */
    protected function setIcon($iconurl)
    {
        $this->feedIcon = $iconurl;
    }

    /**
     * Returns the ChromeOptions object.
     *
     * If the configuration parameter 'headless' is set to true, the
     * argument '--headless' is added. Override this to change or add
     * more options.
     *
     * @return ChromeOptions
     */
    protected function getBrowserOptions()
    {
        $chromeOptions = new ChromeOptions();
        if (Configuration::getConfig('webdriver', 'headless')) {
            $chromeOptions->addArguments(['--headless']);   // --window-size=1024,1024
        }
        return $chromeOptions;
    }

    /**
     * Returns the DesiredCapabilities object for the Chrome browser.
     *
     * The Chrome options are added. Override this to change or add
     * more capabilities.
     *
     * @return WebDriverCapabilities
     */
    protected function getDesiredCapabilities(): WebDriverCapabilities
    {
        $desiredCapabilities = DesiredCapabilities::chrome();
        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $this->getBrowserOptions());
        return $desiredCapabilities;
    }

    /**
     * Constructs the remote webdriver with the url of the remote (Selenium)
     * webdriver server and the desired capabilities.
     *
     * This should be called in collectData() first.
     */
    protected function prepareWebDriver()
    {
        $server = Configuration::getConfig('webdriver', 'selenium_server_url');
        $this->driver = RemoteWebDriver::create($server, $this->getDesiredCapabilities());
    }

    /**
     * Maximizes the remote browser window (often important for reactive sites
     * which change their appearance depending on the window size) and opens
     * the uri set in the constant URI.
     */
    protected function prepareWindow()
    {
        $this->getDriver()->manage()->window()->maximize();
        $this->getDriver()->get($this->getURI());
    }

    /**
     * Closes the remote browser window and shuts down the remote webdriver
     * connection.
     *
     * This must be called at the end of scraping, for example within a
     * 'finally' block.
     */
    protected function cleanUp()
    {
        $this->getDriver()->quit();
    }

    /**
     * Do your web scraping here and fill the $items array.
     *
     * Override this but call parent() first.
     * Don't forget to call cleanUp() at the end.
     */
    public function collectData()
    {
        $this->prepareWebDriver();
        $this->prepareWindow();
    }
}