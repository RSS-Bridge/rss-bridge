<?php
class ClistbyBridge extends BridgeAbstract {

	const NAME = 'Clistby';
	const URI = 'https://clist.by/';
	const DESCRIPTION = 'Return programming contest from clist.by';
	const MAINTAINER = 'nhan_nht';
	const CACHE_TIMEOUT = 14400;

	public function collectData()
	{
		$html = getSimpleHTMLDOM('https://clist.by')
			or returnServerError('Cannot connected');
		$contest = $html->find('#contests', 0);

		if(!is_null($contest)) {
			foreach($contest->find('div[class*="row contest"]') as $element) {
				$item = array();
				$item['categories'] = array();
				$item['title'] = $element->find('div[class*="col-md-7 col-sm-8"]', 0)
										 ->find('span[class=contest_title]', 0)->find('a[title]', 0)->plaintext;

				$item['uri'] = $element->find('div[class*="col-md-7 col-sm-8"]', 0)
									   ->find('span[class=contest_title]', 0)->find('a[title]', 0)->href;
				$item['categories']['start_end_time'] = 'Start-time ' . $element->find('div[class*="col-md-5 col-sm-4"]', 0)
											->find('div[class*=start-time]', 0)->plaintext;
				$item['categories']['Duration'] = 'Duration ' . $element->find('div[class*="col-md-5 col-sm-4"]', 0)
										  ->find('div[class*=duration]', 0)->plaintext;
				$item['categories']['Timeleft']
					= 'Timeleft ' . $element->find('div[class*="col-md-5 col-sm-4"]', 0)
											->find('div[class*=countdown]', 0)->plaintext;
				$item['categories']['Link'] = $element->find('div[class*="col-md-7 col-sm-8"]', 0)
									   ->find('span[class=contest_title]', 0)->find('a[title]', 0)->href;
				$this->items[] = $item;

			}
		}

	}
}
/* End of file filename.php */
