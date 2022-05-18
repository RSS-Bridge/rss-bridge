<?php
class GitlabIssueBridge extends BridgeAbstract {

	const MAINTAINER = 'Mynacol';
	const NAME = 'Gitlab Issue';
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
				'name' => 'User name',
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
		)
	);

	const URL_PATH = '-/issues';
	const COMMENTS_PATH = 'discussions.json';

	public function getName(){
		if ($this->getInput('h')) {
			$name = $this->getInput('h') . '/' . $this->getInput('u') . '/' . $this->getInput('p');
			$name = $name . ' Issue #' . $this->getInput('i');
			return $name;
		} else {
			return parent::getName();
		}
	}

	public function getURI() {
		$uri = 'https://' . $this->getInput('h') . '/' . $this->getInput('u') . '/'
			 . $this->getInput('p') . '/';
		$uri .= static::URL_PATH . '/' . $this->getInput('i');
		return $uri;
	}

	public function getIcon() {
		return 'https://' . $this->getInput('h') . '/favicon.ico';
	}

	public function collectData() {
		/* parse issue description */
		$issue = $this->loadCacheValue('issue.json', static::CACHE_TIMEOUT);
		if (!$issue) {
			$issue = getContents($this->getURI() . '.json');
			$this->saveCacheValue('issue.json', $issue);
		}
		$issue = json_decode($issue, false);
		$issue_html = getSimpleHtmlDomCached($this->getURI());

		$item = array();
		$item['uri'] = $this->getURI();
		$item['uid'] = $issue->id;

		$item['timestamp'] = $issue->updated_at ?? $issue->created_at;

		// fix img src
		foreach ($issue_html->find('img') as $img) {
			$img->src = $img->getAttribute('data-src');
		}
		$authors = $issue_html->find('.issuable-meta a.author-link');
		//array_map(function($e) { return $e->outerhtml; }, $authors);
		$editors = $issue_html->find('.edited-text a.author-link');
		$author_str = implode(' ', $authors);
		if ($editors) {
			$author_str .= ', ' . implode(' ', $editors);
		}
		$item['author'] = defaultLinkTo($author_str, 'https://' . $this->getInput('h') . '/');

		$item['title'] = $issue->title;
		$item['content'] = markdownToHtml($issue->description);

		$this->items[] = $item;

		/* parse issue comments */
		$comments = $this->loadCacheValue('comments.json', static::CACHE_TIMEOUT);
		if (!$comments) {
			$comments = getContents($this->getURI() . '/' . static::COMMENTS_PATH);
			$this->saveCacheValue('comments.json', $comments);
		}
		$comments = json_decode($comments, false);

		foreach ($comments as $value) {
			foreach ($value->notes as $comment) {
				$item = array();
				$item['uri'] = $comment->noteable_note_url;
				$item['uid'] = $comment->id;

				$item['timestamp'] = $comment->last_edited_at ?? $comment->updated_at;
				$author = $comment->last_edited_by ?? $comment->author;
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
