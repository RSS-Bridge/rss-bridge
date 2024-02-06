<?php

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Interactions\WebDriverActions;
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
        $this->scrollIntoView($nextPage);   // or else tooltip might cover link
        $nextPage->click();
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::not(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::xpath('//app-linkable-paginator//li[@id="next-page"]/a[@href="' . $href . '"]')
            )
        ));
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

    /*
     * Converts a string like "vor einigen Minuten" into reasonable numbers.
     * Long and complicated, but we don't want to be more specific than
     * the information we have available. That's why DateInterval doesn't work here.
     */
    protected function calculateTimeAgo(string $timeAgoStr): array
    {
        $now = getdate();
        $secs = $now['seconds'];
        $mins = $now['minutes'];
        $hours = $now['hours'];
        $quantityStr = explode(' ', $timeAgoStr)[1];
        if (in_array($quantityStr, ['einem', 'einer', 'einigen'])) {
            $quantity = 1;
        } else {
            $quantity = intval($quantityStr);
        }
        // substract time ago
        if (str_contains($timeAgoStr, 'Sekunde')) {
            $secs -= $quantity;
        } elseif (str_contains($timeAgoStr, 'Minute')) {
            $mins -= $quantity;
            $secs = 0;
        } elseif (str_contains($timeAgoStr, 'Stunde')) {
            $hours -= $quantity;
            $mins = 0;
            $secs = 0;
        } elseif (str_contains($timeAgoStr, 'Tag')) {
            $hours = 0;
            $mins = 0;
            $secs = 0;
        } else {
            throw new UnexpectedValueException($timeAgoStr);
        }
        // correct carry
        if ($secs < 0) {
            $secs += +60;
            $mins -= 1;
        }
        if ($mins < 0) {
            $mins += 60;
            $hours -= 1;
        }
        if ($hours < 0) {
            $hours += 24;
        }
        return ['seconds' => $secs, 'minutes' => $mins, 'hours' => $hours];
    }

    protected function getTimestamp(RemoteWebElement $item): int
    {
        // get time ago and published date
        $timeAgo = $item->findElement(WebDriverBy::xpath('.//small[contains(@class, "time-ago")]'));
        $this->scrollIntoView($timeAgo);    // to raise tooltip
        $tooltipXpath = '//div[contains(@class, "p-tooltip-text")]';
        $this->getDriver()->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath($tooltipXpath)));
        $publishedStr = $this->getDriver()->findElement(WebDriverBy::xpath($tooltipXpath))->getText();
        // construct reasonable timestamp
        $calculatedTime = $this->calculateTimeAgo($timeAgo->getText());
        $publishedDate = explode('.', explode(' ', $publishedStr)[2]);
        $date = new DateTime();
        $date->setDate($publishedDate[2], $publishedDate[1], $publishedDate[0]);
        $date->setTime($calculatedTime['hours'], $calculatedTime['minutes'], $calculatedTime['seconds']);
        return $date->getTimestamp();
    }

    protected function scrollIntoView(RemoteWebElement $item)
    {
        $actions = new WebDriverActions($this->getDriver());
        $actions->moveToElement($item)->perform();
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
                        // mostly "Direkt vom Auftraggeber" or "GULP Agentur"
                        $feedItem->setAuthor($item->findElement(WebDriverBy::tagName('b'))->getText());
                    }
                    $feedItem->setContent($item->findElement(WebDriverBy::xpath('.//p[@class="description"]'))->getText());
                    $feedItem->setTimestamp($this->getTimestamp($item));

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
