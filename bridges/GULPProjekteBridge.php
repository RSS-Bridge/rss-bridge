<?php

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class GULPProjekteBridge extends WebDriverAbstract
{
    const NAME = 'GULP Projekte';
    const URI = 'https://www.gulp.de/gulp2/g/projekte';
    const DESCRIPTION = 'Projektsuche';
    const MAINTAINER = 'hleskien';

    protected function getBrowserOptions()
    {
        $chromeOptions = parent::getBrowserOptions();
        $chromeOptions->addArguments(['--accept-lang=de']);
        return $chromeOptions;
    }

    public function clickAwayCookieBanner()
    {
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
        $buttonRejectCookies = $this->getDriver()->findElement(WebDriverBy::id('onetrust-reject-all-handler'));
        $buttonRejectCookies->click();
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('onetrust-reject-all-handler')));
    }

    public function clickNextPage()
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

    public function timeAgo2Timestamp(string $timestr): int
    {
        if (str_contains($timestr, 'Minute')) {
            $factor = 60;
        } elseif (str_contains($timestr, 'Stunde')) {
            $factor = 60 * 60;
        } elseif (str_contains($timestr, 'Tag')) {
            $factor = 24 * 60 * 60;
        } else {
            throw new UnexpectedValueException($timestr);
        }
        $quantitystr = explode(' ', $timestr)[1];
        if (($quantitystr == 'einem') || ($quantitystr == 'einer')) {
            $quantity = 1;
        } else {
            $quantity = intval($quantitystr);
        }
        return time() - $quantity * $factor;
    }

    public function collectData()
    {
        parent::collectData();

        try {
            $this->clickAwayCookieBanner();
            $this->setIcon($this->getDriver()->findElement(WebDriverBy::xpath('//link[@rel="shortcut icon"]'))->getAttribute('href'));

            $timestamp = time();
            while (true) {
                $items = $this->getDriver()->findElements(WebDriverBy::tagName('app-project-view'));
                foreach ($items as $item) {
                    $feedItem = new FeedItem();
                    $heading = $item->findElement(WebDriverBy::xpath('.//app-heading-tag/h1/a'));
                    $feedItem->setTitle($heading->getText());
                    $feedItem->setURI('https://www.gulp.de' . $heading->getAttribute('href'));
                    $info = $item->findElement(WebDriverBy::tagName('app-icon-info-list'));
                    // TODO add Projektanbieter image as enclosure?
                    if (str_contains($info->getText(), 'Projektanbieter:')) {
                        $feedItem->setAuthor($info->findElement(WebDriverBy::xpath('.//li/span[2]/span'))->getText());
                    }
                    $feedItem->setContent($item->findElement(WebDriverBy::xpath('.//p[@class="description"]'))->getText());
                    // TODO add tags as categories?
                    $timestamp = $this->timeAgo2Timestamp($item->findElement(WebDriverBy::xpath('.//small[contains(@class, "time-ago")]'))->getText());
                    $feedItem->setTimestamp($timestamp);

                    $this->items[] = $feedItem;
                }

                if ((time() - $timestamp) < (2 * 24 * 60 * 60)) {  // less than two days
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
