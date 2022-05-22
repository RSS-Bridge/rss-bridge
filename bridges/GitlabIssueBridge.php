<?php
class GitlabIssueBridge extends BridgeAbstract {

	const MAINTAINER = 'Mynacol';
	const NAME = 'Gitlab Issue/Merge Request';
	const URI = 'https://gitlab.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns  comments of an issue of a gitlab project';

	const PARAMETERS = array(
		'global' => array(
			'h' => array(
				'name' => 'Gitlab instance host name',
				'exampleValue' => 'gitlab.com',
				'defaultValue' => 'gitlab.com',
				'required' => true
			),
			'u' => array(
				'name' => 'User/Organization name',
				'exampleValue' => 'fdroid',
				'required' => true
			),
			'p' => array(
				'name' => 'Project name',
				'exampleValue' => 'fdroidclient',
				'required' => true
			)

		),
		'Issue comments' => array(
			'i' => array(
				'name' => 'Issue number',
				'type' => 'number',
				'exampleValue' => '2099',
				'required' => true
			)
		),
		'Merge Request comments' => array(
			'i' => array(
				'name' => 'Merge Request number',
				'type' => 'number',
				'exampleValue' => '2099',
				'required' => true
			)
		)
	);

	const ISSUES_PATH = '-/issues';
	const MERGE_REQUESTS_PATH = '-/merge_requests';
	const COMMENTS_PATH = 'discussions.json';

	public function getName(){
		$name = $this->getInput('h') . '/' . $this->getInput('u') . '/' . $this->getInput('p');
		switch ($this->queriedContext) {
			case 'Issue comments':
				$name .= ' Issue #' . $this->getInput('i');
				break;
			case 'Merge Request comments':
				$name .= 'MR !' . $this->getInput('i');
				break;
			default:
				return parent::getName();
		}
		return $name;
	}

	public function getURI() {
		$uri = 'https://' . $this->getInput('h') . '/' . $this->getInput('u') . '/'
			 . $this->getInput('p') . '/';
		switch ($this->queriedContext) {
			case 'Issue comments':
				$uri .= static::ISSUES_PATH;
				break;
			case 'Merge Request comments':
				$uri .= static::MERGE_REQUESTS_PATH;
				break;
			default:
				return $uri;
		}
		$uri .= '/' . $this->getInput('i');
		return $uri;
	}

	public function getIcon() {
		return 'https://' . $this->getInput('h') . '/favicon.ico';
	}

	public function collectData() {
		/* parse issue description */
		$description_uri = $this->getURI() . '.json';
		$description = $this->loadCacheValue($description_uri, static::CACHE_TIMEOUT);
		if (!$description) {
			$description = getContents($description_uri);
			$this->saveCacheValue($description_uri, $description);
		}
		$description = json_decode($description, false);
		$description_html = getSimpleHtmlDomCached($this->getURI());

		$item = array();
		$item['uri'] = $this->getURI();
		$item['uid'] = $item['uri'];

		$item['timestamp'] = $description->updated_at ?? $description->created_at;

		// fix img src
		foreach ($description_html->find('img') as $img) {
			$img->src = $img->getAttribute('data-src');
		}
		$authors = $description_html->find('.issuable-meta a.author-link');
		$editors = $description_html->find('.edited-text a.author-link');
		$author_str = implode(' ', $authors);
		if ($editors) {
			$author_str .= ', ' . implode(' ', $editors);
		}
		$item['author'] = defaultLinkTo($author_str, 'https://' . $this->getInput('h') . '/');

		$item['title'] = $description->title;
		$item['content'] = markdownToHtml($description->description);

		$this->items[] = $item;

		/* parse issue/MR comments */
		$comments_uri = $this->getURI() . '/' . static::COMMENTS_PATH;
		$comments = $this->loadCacheValue($comments_uri, static::CACHE_TIMEOUT);
		if (!$comments) {
			$comments = getContents($comments_uri);
			$this->saveCacheValue($comments_uri, $comments);
		}
		$comments = json_decode($comments, false);

		foreach ($comments as $value) {
			foreach ($value->notes as $comment) {
				$item = array();
				$item['uri'] = $comment->noteable_note_url;
				$item['uid'] = $item['uri'];

				// TODO fix invalid timestamps (fdroid bot)
				$item['timestamp'] = $comment->created_at ?? $comment->updated_at ?? $comment->last_edited_at;
				$author =  $comment->author ?? $comment->last_edited_by;
				$item['author'] = '<img src="' . $author->avatar_url . '" width=24></img> <a href="https://' . $this->getInput('h') . $author->path . '">' . $author->name . ' @' . $author->username . '</a>';

				$content = '';
				if ($comment->system) {
					$content = $comment->note_html;
					if ($comment->type === 'StateNote') {
						$content .= ' the issue';
					}
				} else {
					if ($comment->type === null || $comment->type === 'DiscussionNote') {
						$content = 'commented';
					} else {
						$content = $comment->note_html;
					}
				}
				$item['title'] = $author->name . " $content " . date('(Y-m-d)', strtotime($item['timestamp']));
				$item['content'] = defaultLinkTo($comment->note_html, 'https://' . $this->getInput('h') . '/');

				$this->items[] = $item;
			}
		}
	}
}
