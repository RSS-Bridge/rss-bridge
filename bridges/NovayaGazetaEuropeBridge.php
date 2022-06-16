<?php
class NovayaGazetaEuropeBridge extends BridgeAbstract
{

	const MAINTAINER = 'sqrtminusone';
	const NAME = 'Novaya Gazeta Europe Bridge';
	const URI = 'https://novayagazeta.eu';

	const CACHE_TIMEOUT = 3600; // 1 hour
	const DESCRIPTION = 'Returns articles from Novaya Gazeta Europe';

	const PARAMETERS = array(
		'' => array(
			'language' => array(
				'name' => 'Language',
				'type' => 'list',
				'defaultValue' => 'ru',
				'values' => array(
					'Russian' => 'ru',
					'English' => 'en',
				)
			)
		)
	);


	public function collectData()
	{
		$url = 'https://novayagazeta.eu/api/v1/get/main';
		if ($this->getInput('language') != 'ru') {
			$url .= '?lang=' . $this->getInput('language');
		}

		$json = getContents($url);
		$data = json_decode($json);

		foreach ($data->records as $record) {
			foreach ($record->blocks as $block) {
				if (!property_exists($block, 'date')) {
					continue;
				}
				$body = '';
				if (property_exists($block, 'body') && $block->body !== null) {
					$body = self::convertBody($block->body, $block->lead);
				} else {
					$record_json = getContents("https://novayagazeta.eu/api/v1/get/record?slug={$block->slug}");
					$record_data = json_decode($record_json);
					$body = self::convertBody($record_data->record->body, $record_data->record->lead);
				}
				$title = strip_tags($block->title);
				if (property_exists($block, 'subtitle') && mb_strlen($block->subtitle) > 0) {
					$title .= '. ' . strip_tags($block->subtitle);
				}
				$item = array(
					'uri' => self::URI . '/articles/' . $block->slug,
					'title' => $title,
					'author' => join(', ', array_map(function ($author) {
						return $author->name;
					}, $block->authors)),
					'timestamp' => $block->date / 1000,
					'categories' => $block->tags,
					'content' => $body
				);
				$this->items[] = $item;
			}
		}
		usort($this->items, function ($item1, $item2) {
			return $item1['timestamp'] < $item2['timestamp'];
		});
	}

	private function convertBody($data, $lead) {
		$body = '';
		if ($lead !== null) {
			$body .= "<p><b>{$lead}</b></p>";
		}
		if (!is_null($data)) {
			foreach ($data as $datum) {
				$body .= self::convertElement($datum);
			}
		}
		return $body;
	}

	private function convertElement($datum) {
		switch ($datum->type) {
			case 'text':
				return $datum->data;
				break;
			case 'image/single':
				$alt = strip_tags($datum->data);
				$res = "<figure><img src=\"{$datum->previewUrl}\" alt=\"{$alt}\" />";
				if ($datum->data !== null) {
					$res .= "<figcaption>{$datum->data}</figcaption>";
				}
				$res .= "</figure>";
				break;
			case 'text/quote':
				return "<figure><quote>{$datum->data}</quote></figure><br>";
				break;
			case 'embed/native':
				$desc = $datum->link;
				if (property_exists($datum, 'caption')) {
					$desc = $datum->caption;
				}
				return "<p><a link=\"{$datum->link}\">{$desc}</a></p>";
				break;
			case 'text/framed':
				$res = '';
				if (property_exists($datum, 'typeDisplay')) {
					$res .= "<p><b>{$datum->typeDisplay}</b></p>";
				}
				$res .= "<p>{$datum->data}</p>";
				if (property_exists($datum, 'attachment')
					&& property_exists($datum->attachment, 'type')) {
					$res .= self::convertElement($datum->attachment);
				}
				return $res;
			default:
				return '';
		}
	}
}
