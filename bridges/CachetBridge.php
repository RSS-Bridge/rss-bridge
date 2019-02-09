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
		return $this->getInput('host') === null ? 'https://demo.cachethq.io/' : $this->getInput('host');
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
		$ping = getContents($this->getURI() . '/api/v1/ping');
		if (!$this->validatePing($ping)) {
			returnClientError('Provided URI is invalid!');
		}

		$incidents = getContents($this->getURI() . '/api/v1/incidents');
		if (!$this->isValidJSON($incidents)) {
			returnClientError('/api/v1/incidents returned no valid json');
		}

		$incidents = json_decode($incidents);

		usort($incidents->data, function ($a, $b) {
			$timeA = strtotime($a->updated_at);
			$timeB = strtotime($b->updated_at);
			return $timeA > $timeB ? -1 : 1;
		});

		foreach ($incidents->data as $incident) {
			$componentName = $this->getComponentName($incident->component_id);
			$content = str_replace("\r\n", "\n<br>", $incident->message);
			$uidOrig = $incident->permalink . $incident->created_at;
			$uid = hash('sha512', $uidOrig);
			$item = [];
			$item['uri'] = $incident->permalink;
			$item['title'] = $incident->name;
			$item['timestamp'] = strtotime($incident->created_at);
			$item['content'] = $content;
			if ($componentName !== '') {
				$item['categories'] = [
					$componentName
				];
			}
			$item['uid'] = $uid;
			$this->items[] = $item;
		}
	}
}
