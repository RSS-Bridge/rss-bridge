<?php

class PicartoBridge extends BridgeAbstract
{
	const NAME = 'Picarto';
	const URI = 'https://picarto.tv';
	const DESCRIPTION = 'Returns the online status of a picarto.tv channel';
	const MAINTAINER = 'No maintainer';
	const CACHE_TIMEOUT = 300;// 5min, same as TwitchBridge
	const PARAMETERS = array(
		array(
			'channel' => array(
				'name' => 'Channel',
				'type' => 'text',
				'required' => true,
				'title' => 'Lowercase channel name as seen in channel URL'
			)
		)
	);

	// See "https://api.picarto.tv/" for Picarto API docs
	const API_BASE_URI = 'https://api.picarto.tv/api/v1/channel/name/';
	const TIMEZONE_DEFAULT = 'Europe/Berlin';

	public function collectData()
	{
		$channelName = $this->getInput('channel');
		$apiUrl = $API_BASE_URI . $channelName;
		$picartoResponse = json_decode(getContents($apiUrl), true);

		if ($picartoResponse['online']) {
			$item = array();

			$item['uri'] = getURI() . '/' . $channel;
			$item['title'] = $picartoResponse['name'] . ' is now online';

			$rawDate = $picartoResponse['last_live'];

			try {
				$date = date_create_from_format('Y-m-d H:i:s T', $rawDate);
			} catch (Throwable $t) { // PHP 7
				$timezone = timezone_open($TIMEZONE_DEFAULT);
				$date = date_create_from_format('Y-m-d H:i:s', $rawDate, $timezone);
			} catch (Exception $e) { // PHP 5
				$timezone = timezone_open($TIMEZONE_DEFAULT);
				$date = date_create_from_format('Y-m-d H:i:s', $rawDate, $timezone);
			}

			$item['timestamp'] = $date->getTimestamp();

			// Display stream preview as content
			$item['content'] = '<img src="'
				. $picartoResponse['thumbnails']['tablet']
				. '"/>';

			$this->items[] = $item;
		}
	}

	public function getName()
	{
		return parent::getName() . ' - ' . $this->getInput('channel');
	}

	public function detectParameters($url)
	{
		$params = array();

		$regex = '/^(https?:\/\/)?(www\.)?picarto\.tv\/([^\/?\n]+)/';
		if (preg_match($regex, $url, $matches) > 0) {
			$params['channel'] = urldecode($matches[1]);
			return $params;
		}

		return null;
	}
}
