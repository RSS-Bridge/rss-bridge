<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "classes/GenericExtractor.php";
require_once "classes/PostExtractor.php";
require_once "classes/PartExtractor.php";
require_once "Utilities.php";

class Extractor extends GenericExtractor {
	private $postExtractor;

	const defaultOptions = array(
		'extractComments' => true,
		'postsAmount' => 20,
	);

	public function __construct($getDoms, $log, $options = array()) {
		parent::__construct($getDoms, $log, $options);

		foreach(self::defaultOptions as $name => $value) {
			if(!isset($options[$name])) {
				$this->options[$name] = self::defaultOptions[$name];
			} else {
				$this->options[$name] = $options[$name];
			}
		}

		$this->postExtractor = new PostExtractor($getDoms, $log, $this->options);
	}

	public function getPostsFromSource($sourceId) {
		$posts = array();
		$urls = array();

		$sourceUrl = 'https://vk.com/' . $sourceId;
		$sourceDom = $this->getDom($sourceUrl, 'source');

		if($sourceDom === null) {
			throw new \Exception('Failed to get source dom from this url: ' . $sourceUrl);
		}

		$postsProcessed = 0;
		foreach($sourceDom->find('.post') as $postElem) {
			if($postsProcessed >= $this->options['postsAmount']) {
				break;
			} else {
				$postsProcessed++;
			}

			$postId = substr($postElem->getAttribute('id'), 4);
			if(preg_match('/-?\d+_\d+/', $postId)) {
				$urls[] = getPostUrlFromId($postId);
			} else {
				$this->log("Failed to extract post: failed to extract post\'s id");
				$posts[] = null;
			}
		}

		$postsDoms = $this->getDoms($urls, 'post');

		foreach($postsDoms as $url => $postDom) {
			if($postDom === null) {
				$this->log("Failed to extract post: failed to get post dom from this url: $url");
				$posts[] = null;
				continue;
			}

			$this->postExtractor->setDom($postDom);

			if($this->postExtractor->needsMobileDom()) {
				$postId = getPostIdFromUrl($url);
				$mobilePostUrl = getMobilePostUrlFromId($postId);
				$mobileDom = $this->getDom($mobilePostUrl, 'page');

				if($mobileDom === null) {
					$this->log("Failed to extract post: failed to get post\'s mobile dom from this url: $mobilePostUrl");
					$posts[] = null;
					continue;
				}

				$this->postExtractor->setMobileDom($mobileDom);
			}

			try {
				$posts[] = $this->postExtractor->extractPost();
			} catch(\Exception $e) {
				$this->log('Failed to extract post: ' . $e->getMessage());
				$posts[] = null;
			}
		}

		return $posts;
	}

   	public function getPostById($postId) {
		$url = getPostUrlFromId($postId);

		$postDom = $this->getDom($url, 'post');
		if($postDom === null) {
			throw new \Exception('Failed to get post dom from this url: ' . $url);
		}

		$this->postExtractor->setDom($postDom);

		if($this->postExtractor->needsMobileDom()) {
			$mobilePostUrl = getMobilePostUrlFromId($postId);
			$mobileDom = $this->getDom($mobilePostUrl, 'post');
			if($mobileDom === null) {
				throw new \Exception('Failed to get post\'s mobile dom from this url: ' . $mobilePostUrl);
			}
			$this->postExtractor->setMobileDom($mobileDom);
		}

		return $this->postExtractor->extractPost();
	}
}
?>
