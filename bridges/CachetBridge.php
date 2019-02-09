<?php
class CachetBridge extends BridgeAbstract {
	const NAME = 'Cachet Bridge';
	const URI = 'https://cachethq.io/';
	const DESCRIPTION = 'Returns status updates from any Cachet installation';
	const MAINTAINER  = 'klimplant';
	const PARAMETERS = array(
		array(
			'host' => array(
				'name' => 'Cachet installation',
				'type' => 'text',
				'required' => true,
				'title' => 'The URL of the Cache installation',
				'exampleValue' => 'https://demo.cachethq.io/',
			)
		)
	);
	const CACHE_TIMEOUT = 300;

	private $componentCache = [];

	public function getURI() {
		return $this->getInput('host') === null ? 'https://cachethq.io/' : $this->getInput('host');
	}

	/**
	 * Validates if a string is valid JSON
	 *
	 * @param string $string
	 * @return boolean
	 */
	private function isValidJSON($string) {
		return json_decode($string);
	}

	/**
	 * Validates the ping request to the cache API
	 *
	 * @param string $ping
	 * @return boolean
	 */
	private function validatePing($ping) {
		if (!$this->isValidJSON($ping)) {
			return false;
		}
		$ping = json_decode($ping);
		return $ping->data === 'Pong!';
	}

	/**
	 * Returns the component name of a cachat component
	 *
	 * @param integer $id
	 * @return string
	 */
	private function getComponentName($id) {
		if ($id === 0) {
			return '';
		}
		if (array_key_exists($id, $this->componentCache)) {
			return $this->componentCache[$id];
		}

		$component = getContents($this->getURI() . '/api/v1/components/' . $id);
		if (!$this->isValidJSON($component)) {
			return '';
		}
		$component = json_decode($component);
		return $component->data->name;
	}

	public function collectData() {
		$ping = getContents(urljoin($this->getURI(), '/api/v1/ping'));
		if (!$this->validatePing($ping)) {
			returnClientError('Provided URI is invalid!');
		}

		$url = urljoin($this->getURI(), '/api/v1/incidents');
		$incidents = getContents($url);
		if (!$this->isValidJSON($incidents)) {
			returnClientError('/api/v1/incidents returned no valid json');
		}

		$incidents = json_decode($incidents);

		$maxPage = $incidents->meta->pagination->total_pages;
		$minPage = $maxPage > 1 ? $maxPage - 1 : 1;
		for ($p = $maxPage; $p >= $minPage; $p--) {
			if ($p !== 1) {
				$url = urljoin($this->getURI(), '/api/v1/incidents?page=' . $p);
				$incidents = getContents($url);
				if (!$this->isValidJSON($incidents)) {
					returnClientError('/api/v1/incidents returned no valid json');
				}

				$incidents = json_decode($incidents);
			}

			usort($incidents->data, function ($a, $b) {
				$timeA = strtotime($a->updated_at);
				$timeB = strtotime($b->updated_at);
				return $timeA > $timeB ? -1 : 1;
			});

			foreach ($incidents->data as $incident) {

				if (isset($incident->permalink)) {
					$permalink = $incident->permalink;
				} else {
					$permalink = urljoin($this->getURI(), '/incident/' . $incident->id);
				}

				$title = $incident->human_status . ': ' . $incident->name;
				$content = nl2br($incident->message);
				$componentName = $this->getComponentName($incident->component_id);
				$uidOrig = $permalink . $incident->created_at;
				$uid = hash('sha512', $uidOrig);
				$timestamp = strtotime($incident->created_at);
				$categories = [];
				$categories[] = $incident->human_status;
				if ($componentName !== '') {
					$categories[] = $componentName;
				}

				$item = [];
				$item['uri'] = $permalink;
				$item['title'] = $title;
				$item['timestamp'] = $timestamp;
				$item['content'] = $content;
				$item['uid'] = $uid;
				$item['categories'] = $categories;

				$this->items[] = $item;
				if (count($this->items) === 20) {
					break;
				}
			}
		}

	}
}
