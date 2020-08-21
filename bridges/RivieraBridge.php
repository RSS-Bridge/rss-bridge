<?php

class RivieraBridge extends BridgeAbstract {
	const NAME = 'Sala La Riviera';
	const URI = 'https://salariviera.com/conciertos-riviera/';
	const DESCRIPTION = 'Concerts in Sala La Riviera, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array(); // Can be omitted!
	const CACHE_TIMEOUT = 1;

	private function eventToItem($event, string $month) : array {
		$event_date_day = $event->find('.event-date-day', 0);
		$event_title    = $event->find('.event-title', 0);
		$event_status_wrapper = $event->find('.event-status-wrapper', 0);

		$date  = $event_date_day->innertext . ' ' . $month;
		$title = $event_title->find('a', 0)->innertext;
		$link  = $event_title->find('a', 0)->href;
		//$buy   = $event_status_wrapper->innertext;

		$item = array(
			'title'   => $date . ' - ' . $title,
			//'content' => $date . '<br><div style=\'font-size:150%; font-weigth:bold\'>' . $title . '</div><br>' . $buy,
			'uri'     => $link,
		);

		return $item;
	}

	private function getEventItemsPage(int $page) {
		$events = array();

		$html = getSimpleHTMLDOM(self::URI . '/page/' . $page);
		$month = '';

		foreach ($html->find('.gdlr-list-event, .gdlr-list-by-month-header')
			as $event) {
			if ($event->class == 'gdlr-list-by-month-header') {
				$month = $event->innertext;
			} else {
				$item = $this->eventToItem($event, $month);
				$events[] = $item;
			}
		}

		return array_reverse($events);
	}

	private function getEventItems() : array {
		$page = 0;
		$events = array();
		do {
			$current = $this->getEventItemsPage($page);
			$events  = array_merge($current, $events);
			$page++;
		} while (sizeof($current) > 0);
		return $events;
	}

	public function collectData() {
		$this->items = array_merge($this->items, $this->getEventItems());
	}
}
