<?php

class CollegeDeFranceBridge extends BridgeAbstract
{
    const MAINTAINER = 'pit-fgfjiudghdf';
    const NAME = 'CollegeDeFrance';
    const URI = 'https://www.college-de-france.fr/';
    const CACHE_TIMEOUT = 10800; // 3h
    const DESCRIPTION = 'Returns the latest audio and video from CollegeDeFrance';

    public function collectData()
    {
        $months = [
            '01' => 'janv.',
            '02' => 'févr.',
            '03' => 'mars',
            '04' => 'avr.',
            '05' => 'mai',
            '06' => 'juin',
            '07' => 'juil.',
            '08' => 'août',
            '09' => 'sept.',
            '10' => 'oct.',
            '11' => 'nov.',
            '12' => 'déc.'
        ];

        // The "API" used by the site returns a list of partial HTML in this form
        /* <li>
         *  <a href="/site/thomas-romer/guestlecturer-2016-04-15-14h30.htm" data-target="after">
         *      <span class="date"><span class="list-icon list-icon-video"></span>
         *      <span class="list-icon list-icon-audio"></span>15 avr. 2016</span>
         *      <span class="lecturer">Christopher Hays</span>
         *      <span class='title'>Imagery of Divine Suckling in the Hebrew Bible and the Ancient Near East</span>
         *  </a>
         * </li>
         */
        $html = getSimpleHTMLDOM(self::URI
        . 'components/search-audiovideo.jsp?fulltext=&siteid=1156951719600&lang=FR&type=all');

        foreach ($html->find('a[data-target]') as $element) {
            $item = [];
            $item['title'] = $element->find('.title', 0)->plaintext;

            // Most relative URLs contains an hour in addition to the date, so let's use it
            // <a href="/site/yann-lecun/course-2016-04-08-11h00.htm" data-target="after">
            //
            // Sometimes there's an __1, perhaps it signifies an update
            // "/site/patrick-boucheron/seminar-2016-05-03-18h00__1.htm"
            //
            // But unfortunately some don't have any hours info
            // <a href="/site/institut-physique/
            // The-Mysteries-of-Decoherence-Sebastien-Gleyzes-[Video-3-35].htm" data-target="after">
            $timezone = new DateTimeZone('Europe/Paris');

            // strpos($element->href, '201') will break in 2020 but it'll
            // probably break prior to then due to site changes anyway
            $d = DateTime::createFromFormat(
                '!Y-m-d-H\hi',
                substr($element->href, strpos($element->href, '201'), 16),
                $timezone
            );

            if (!$d) {
                $d = DateTime::createFromFormat(
                    '!d m Y',
                    trim(str_replace(
                        array_values($months),
                        array_keys($months),
                        $element->find('.date', 0)->plaintext
                    )),
                    $timezone
                );
            }

            $item['timestamp'] = $d->format('U');
            $item['content'] = $element->find('.lecturer', 0)->innertext
            . ' - '
            . $element->find('.title', 0)->innertext;

            $item['uri'] = self::URI . $element->href;
            $this->items[] = $item;
        }
    }
}
