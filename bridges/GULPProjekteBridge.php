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

    protected function getBrowserOptions()
    {
        $chromeOptions = parent::getBrowserOptions();
        $chromeOptions->addArguments(['--accept-lang=de']);
        return $chromeOptions;
    }

    protected function clickAwayCookieBanner()
    {
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
        $buttonRejectCookies = $this->getDriver()->findElement(WebDriverBy::id('onetrust-reject-all-handler'));
        $buttonRejectCookies->click();
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
    }

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

    protected function timeAgo2Timestamp(string $timestr): int
    {
        if (str_contains($timestr, 'Sekunde')) {
            $factor = 1;
        } elseif (str_contains($timestr, 'Minute')) {
            $factor = 60;
        } elseif (str_contains($timestr, 'Stunde')) {
            $factor = 60 * 60;
        } elseif (str_contains($timestr, 'Tag')) {
            $factor = 24 * 60 * 60;
        } else {
            throw new UnexpectedValueException($timestr);
        }
        $quantitystr = explode(' ', $timestr)[1];
        if (($quantitystr == 'einem') || ($quantitystr == 'einer') || ($quantitystr == 'einigen')) {
            $quantity = 1;
        } else {
            $quantity = intval($quantitystr);
        }
        return time() - $quantity * $factor;
    }

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

    public function collectData()
    {
        parent::collectData();

        try {
            $this->clickAwayCookieBanner();
            $this->setIcon($this->getDriver()->findElement(WebDriverBy::xpath('//link[@rel="shortcut icon"]'))->getAttribute('href'));

            while (true) {
                $items = $this->getDriver()->findElements(WebDriverBy::tagName('app-project-view'));
                foreach ($items as $item) {
                    $feedItem = new FeedItem();
                    $heading = $item->findElement(WebDriverBy::xpath('.//app-heading-tag/h1/a'));
                    $feedItem->setTitle($heading->getText());
                    $feedItem->setURI('https://www.gulp.de' . $heading->getAttribute('href'));
                    $info = $item->findElement(WebDriverBy::tagName('app-icon-info-list'));
                    if ($logo = $this->getLogo($item)) {
                        $feedItem->setEnclosures([$logo]);
                    }
                    if (str_contains($info->getText(), 'Projektanbieter:')) {
                        $feedItem->setAuthor($info->findElement(WebDriverBy::xpath('.//li/span[2]/span'))->getText());
                    } else {
                        # mostly "Direkt vom Auftraggeber" or "GULP Agentur"
                        $feedItem->setAuthor($item->findElement(WebDriverBy::tagName('b'))->getText());
                    }
                    $feedItem->setContent($item->findElement(WebDriverBy::xpath('.//p[@class="description"]'))->getText());
                    $timeAgo = $item->findElement(WebDriverBy::xpath('.//small[contains(@class, "time-ago")]'))->getText();
                    $timestamp = $this->timeAgo2Timestamp($timeAgo);
                    $feedItem->setTimestamp($timestamp);

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
