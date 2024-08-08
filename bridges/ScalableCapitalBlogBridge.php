<?php

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class ScalableCapitalBlogBridge extends WebDriverAbstract
{
    const NAME = 'Scalable Capital Blog';
    const URI = 'https://de.scalable.capital/blog';
    const DESCRIPTION = 'Alle Artikel';
    const MAINTAINER = 'hleskien';

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
     * Puts the content of the first page into the $items array.
     *
     * @throws Facebook\WebDriver\Exception\NoSuchElementException
     * @throws Facebook\WebDriver\Exception\TimeoutException
     */
    public function collectData()
    {
        parent::collectData();

        try {
            // wait until last item is loaded
            $this->getDriver()->wait()->until(WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::xpath('//div[contains(@class, "articles")]//div[@class="items"]//div[contains(@class, "item")][15]')
            ));
            $this->setIcon($this->getDriver()->findElement(WebDriverBy::xpath('//link[@rel="shortcut icon"]'))->getAttribute('href'));

            $items = $this->getDriver()->findElements(WebDriverBy::xpath('//div[contains(@class, "articles")]//div[@class="items"]//div[contains(@class, "item")]'));
            foreach ($items as $item) {
                $feedItem = [];

                $feedItem['enclosures'] = ['https://de.scalable.capital' . $item->findElement(WebDriverBy::tagName('img'))->getAttribute('src')];

                $heading = $item->findElement(WebDriverBy::tagName('a'));
                $feedItem['title'] = $heading->getText();

                $feedItem['uri'] = 'https://de.scalable.capital' . $heading->getAttribute('href');
                $feedItem['content'] = $item->findElement(WebDriverBy::xpath('.//div[@class="summary"]'))->getText();

                $date = $item->findElement(WebDriverBy::xpath('.//div[@class="published-date"]'))->getText();
                $feedItem['timestamp'] = $this->formatItemTimestamp($date);

                $feedItem['author'] = $item->findElement(WebDriverBy::xpath('.//div[@class="author"]'))->getText();

                $this->items[] = $feedItem;
            }
        } finally {
            $this->cleanUp();
        }
    }

    /**
     * Converts the given date (dd.mm.yyyy) into a timestamp.
     *
     * @param $value string
     * @return int
     */
    protected function formatItemTimestamp($value)
    {
        $formatter = new IntlDateFormatter('de', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        return $formatter->parse($value);
    }
}