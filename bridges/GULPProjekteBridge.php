<?php

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class GULPProjekteBridge extends WebDriverAbstract
{
    const NAME = 'GULP Projekte';
    const URI = 'https://www.gulp.de/gulp2/g/projekte';
    const DESCRIPTION = 'Projektsuche';
    const MAINTAINER = 'hleskien';

    const MAXITEMS = 60;

    /**
     * Adds accept language german to the Chrome Options.
     *
     * @return Facebook\WebDriver\Chrome\ChromeOptions
     */
    protected function getBrowserOptions()
    {
        $chromeOptions = parent::getBrowserOptions();
        $chromeOptions->addArguments(['--accept-lang=de']);
        return $chromeOptions;
    }

    /**
     * @throws Facebook\WebDriver\Exception\NoSuchElementException
     * @throws Facebook\WebDriver\Exception\TimeoutException
     */
    protected function clickAwayCookieBanner()
    {
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
        $buttonRejectCookies = $this->getDriver()->findElement(WebDriverBy::id('onetrust-reject-all-handler'));
        $buttonRejectCookies->click();
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
    }

    /**
     * @throws Facebook\WebDriver\Exception\NoSuchElementException
     * @throws Facebook\WebDriver\Exception\TimeoutException
     */
    protected function clickNextPage()
    {
        $nextPage = $this->getDriver()->findElement(WebDriverBy::xpath('//app-linkable-paginator//li[@id="next-page"]/a'));
        $href = $nextPage->getAttribute('href');
        $nextPage->click();
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::not(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::xpath('//app-linkable-paginator//li[@id="next-page"]/a[@href="' . $href . '"]')
            )
        ));
    }

    /**
     * Returns the uri of the 'Projektanbieter' logo or false if there is
     * no logo present in the item.
     *
     * @return string | false
     */
    protected function getLogo(RemoteWebElement $item)
    {
        try {
            $logo = $item->findElement(WebDriverBy::tagName('img'))->getAttribute('src');
            if (str_starts_with($logo, 'http')) {
                // different domain
                return $logo;
            } else {
                // relative path
                $remove = substr(self::URI, strrpos(self::URI, '/') + 1);
                return substr(self::URI, 0, -strlen($remove)) . $logo;
            }
        } catch (NoSuchElementException $e) {
            return false;
        }
    }

    /**
     * Converts a string like "vor einigen Minuten" into a reasonable timestamp.
     * Long and complicated, but we don't want to be more specific than
     * the information we have available.
     *
     * @throws Exception If the DateInterval can't be parsed.
     */
    protected function getTimestamp(string $timeAgo): int
    {
        $dateTime = new DateTime();
        $dateArray = explode(' ', $dateTime->format('Y m d H i s'));
        $quantityStr = explode(' ', $timeAgo)[1];
        // convert possible word into a number
        if (in_array($quantityStr, ['einem', 'einer', 'einigen'])) {
            $quantity = 1;
        } else {
            $quantity = intval($quantityStr);
        }
        // subtract time ago + inferior units for lower precision
        if (str_contains($timeAgo, 'Sekunde')) {
            $interval = new DateInterval('PT' . $quantity . 'S');
        } elseif (str_contains($timeAgo, 'Minute')) {
            $interval = new DateInterval('PT' . $quantity . 'M' . $dateArray[5] . 'S');
        } elseif (str_contains($timeAgo, 'Stunde')) {
            $interval = new DateInterval('PT' . $quantity . 'H' . $dateArray[4] . 'M' . $dateArray[5] . 'S');
        } elseif (str_contains($timeAgo, 'Tag')) {
            $interval = new DateInterval('P' . $quantity . 'DT' . $dateArray[3] . 'H' . $dateArray[4] . 'M' . $dateArray[5] . 'S');
        } else {
            throw new UnexpectedValueException($timeAgo);
        }
        $dateTime = $dateTime->sub($interval);
        return $dateTime->getTimestamp();
    }

    /**
     * The main loop which clicks through search result pages and puts
     * the content into the $items array.
     *
     * @throws Facebook\WebDriver\Exception\NoSuchElementException
     * @throws Facebook\WebDriver\Exception\TimeoutException
     */
    public function collectData()
    {
        parent::collectData();

        try {
            $this->clickAwayCookieBanner();
            $this->setIcon($this->getDriver()->findElement(WebDriverBy::xpath('//link[@rel="shortcut icon"]'))->getAttribute('href'));

            while (true) {
                $items = $this->getDriver()->findElements(WebDriverBy::tagName('app-project-view'));
                foreach ($items as $item) {
                    $feedItem = [];

                    $heading = $item->findElement(WebDriverBy::xpath('.//app-heading-tag/h1/a'));
                    $feedItem['title'] = $heading->getText();
                    $feedItem['uri'] = 'https://www.gulp.de' . $heading->getAttribute('href');
                    $info = $item->findElement(WebDriverBy::tagName('app-icon-info-list'));
                    if ($logo = $this->getLogo($item)) {
                        $feedItem['enclosures'] = [$logo];
                    }
                    if (str_contains($info->getText(), 'Projektanbieter:')) {
                        $feedItem['author'] = $info->findElement(WebDriverBy::xpath('.//li/span[2]/span'))->getText();
                    } else {
                        // mostly "Direkt vom Auftraggeber" or "GULP Agentur"
                        $feedItem['author'] = $item->findElement(WebDriverBy::tagName('b'))->getText();
                    }
                    $feedItem['content'] = $item->findElement(WebDriverBy::xpath('.//p[@class="description"]'))->getText();
                    $timeAgo = $item->findElement(WebDriverBy::xpath('.//small[contains(@class, "time-ago")]'))->getText();
                    $feedItem['timestamp'] = $this->getTimestamp($timeAgo);

                    $this->items[] = $feedItem;
                }

                if (count($this->items) < self::MAXITEMS) {
                    $this->clickNextPage();
                } else {
                    break;
                }
            }
        } finally {
            $this->cleanUp();
        }
    }
}
