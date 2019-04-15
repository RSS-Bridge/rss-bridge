<?php
class MeetupBridge extends BridgeAbstract {

	const MAINTAINER = 'quentinus95';
	const NAME = 'Meetup events';
	const URI = 'https://www.meetup.com';
	const CACHE_TIMEOUT = 1800;
	const DESCRIPTION = 'Returns the upcoming meetup events.';
	const PARAMETERS = array(
		'All upcoming events' => array(
			'm' => array(
				'name' => 'Member ID',
				'required' => true,
				'exampleValue' => '123456789',
				'title' => 'Taken from Meetup profile URL, e.g. https://www.meetup.com/fr-FR/members/[Member ID]'
			),
			'k' => array(
				'name' => 'API Key',
				'required' => true,
				'exampleValue' => 'e775afaa89658359d474e85167c467',
				'title' => 'Taken from https://secure.meetup.com/fr-FR/meetup_api/key/'
			)
		)
	);

	public function getIcon() {
		return 'https://secure.meetupstatic.com/s/img/68780390453345256452178/favicon.ico';
	}

	public function collectData(){
		$json = json_decode(
			getContents(
				'https://api.meetup.com/2/events?member_id=' . $this->getInput('m') . '&key=' . $this->getInput('k')
			)
		) or returnServerError('Error while downloading the website content');

		foreach($json->results as $result) {
			$item = [];

			$item['uri'] = $result->event_url;
			$item['title'] = $result->name;
			$item['timestamp'] = intval($result->updated) / 1000;
			$item['author'] = $result->group->name;
			$item['content'] = html_entity_decode(
				$result->venue->name . '<br />' .
				$result->venue->address_1 . '<br />' .
				$result->venue->city . '-' . $result->venue->localized_country_name . '<br /><br />' .
				(new DateTime('@' . intval($result->time) / 1000))->format("d/m/Y - H:i") . '<br /><br />' .
				$result->description

			);

			$this->items[] = $item;
		}
	}
}
