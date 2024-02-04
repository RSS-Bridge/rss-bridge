<?php

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class ScalableCapitalBlogBridge extends WebDriverAbstract
{
    const NAME = 'Scalable Capital Blog';
    const URI = 'https://de.scalable.capital/blog';
    const DESCRIPTION = 'Alle Artikel';
    const MAINTAINER = 'hleskien';

    protected function getBrowserOptions()
    {
        $chromeOptions = parent::getBrowserOptions();
        $chromeOptions->addArguments(['--accept-lang=de']);
        return $chromeOptions;
    }

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
                $feedItem = new FeedItem();

                $feedItem->setEnclosures(['https://de.scalable.capital' . $item->findElement(WebDriverBy::tagName('img'))->getAttribute('src')]);
                $heading = $item->findElement(WebDriverBy::tagName('a'));
                $feedItem->setTitle($heading->getText());
                $feedItem->setURI('https://de.scalable.capital' . $heading->getAttribute('href'));
                $feedItem->setContent($item->findElement(WebDriverBy::xpath('.//div[@class="summary"]'))->getText());
                $date = $item->findElement(WebDriverBy::xpath('.//div[@class="published-date"]'))->getText();
                $feedItem->setTimestamp($this->formatItemTimestamp($date));
                $feedItem->setAuthor($item->findElement(WebDriverBy::xpath('.//div[@class="author"]'))->getText());

                $this->items[] = $feedItem;
            }
        } finally {
            $this->cleanUp();
        }
    }

    protected function formatItemTimestamp($value)
    {
        $formatter = new IntlDateFormatter('de', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        return $formatter->parse($value);
    }
}