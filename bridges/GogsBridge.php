<?php
class GogsBridge extends BridgeAbstract {

	const NAME = 'Gogs';
	const URI = 'https://gogs.io';
	const DESCRIPTION = 'Returns the latest issues, commits or releases';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 300; // 5 minutes

	const PARAMETERS = array(
		'global' => array(
			'host' => array(
				'name' => 'Host',
				'exampleValue' => 'https://gogs.io',
				'required' => true,
				'title' => 'Host name without trailing slash',
			),
			'user' => array(
				'name' => 'Username',
				'exampleValue' => 'gogs',
				'required' => true,
				'title' => 'User name as it appears in the URL',
			),
			'project' => array(
				'name' => 'Project name',
				'exampleValue' => 'gogs',
				'required' => true,
				'title' => 'Project name as it appears in the URL',
			),
		),
		'Commits' => array(
			'branch' => array(
				'name' => 'Branch name',
				'defaultValue' => 'master',
				'required' => true,
				'title' => 'Branch name as it appears in the URL',
			),
		),
		'Issues' => array(
			'include_description' => array(
				'name' => 'Include issue description',
				'type' => 'checkbox',
				'title' => 'Activate to include the issue description',
			),
		),
		'Single issue' => array(
			'issue' => array(
				'name' => 'Issue number',
				'type' => 'number',
				'exampleValue' => 102,
				'required' => true,
				'title' => 'Issue number from the issues list',
			),
		),
		'Releases' => array(),
	);

	private $title = '';

	/**
	 * Note: detectParamters doesn't make sense for this bridge because there is
	 * no "single" host for this service. Anyone can host it.
	 */

	public function getURI() {
		switch($this->queriedContext) {
			case 'Commits': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/commits/' . $this->getInput('branch');
			} break;
			case 'Issues': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/issues/';
			} break;
			case 'Single issue': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/issues/' . $this->getInput('issue');
			} break;
			case 'Releases': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/releases/';
			} break;
			default: return parent::getURI();
		}
	}

	public function getName() {
		switch($this->queriedContext) {
			case 'Commits':
			case 'Issues':
			case 'Releases': return $this->title . ' ' . $this->queriedContext;
			case 'Single issue': return $this->title . ' Issue ' . $this->getInput('issue');
			default: return parent::getName();
		}
	}

	public function getIcon() {
		return 'https://gogs.io/img/favicon.ico';
	}

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI());

		$html = defaultLinkTo($html, $this->getURI());

		$this->title = $html->find('[property="og:title"]', 0)->content;

		switch($this->queriedContext) {
			case 'Commits': {
				$this->collectCommitsData($html);
			} break;
			case 'Issues': {
				$this->collectIssuesData($html);
			} break;
			case 'Single issue': {
				$this->collectSingleIssueData($html);
			} break;
			case 'Releases': {
				$this->collectReleasesData($html);
			} break;
		}

	}

	protected function collectCommitsData($html) {
		$commits = $html->find('#commits-table tbody tr')
			or returnServerError('Unable to find commits');

		foreach($commits as $commit) {
			$this->items[] = array(
				'uri' => $commit->find('a.sha', 0)->href,
				'title' => $commit->find('.message span', 0)->plaintext,
				'author' => $commit->find('.author', 0)->plaintext,
				'timestamp' => $commit->find('.time-since', 0)->title,
				'uid' => $commit->find('.sha', 0)->plaintext,
			);
		}
	}

	protected function collectIssuesData($html) {
		$issues = $html->find('.issue.list li')
			or returnServerError('Unable to find issues');

		foreach($issues as $issue) {
			$uri = $issue->find('a', 0)->href;

			$item = array(
				'uri' => $uri,
				'title' => $issue->find('.label', 0)->plaintext . ' | ' . $issue->find('a.title', 0)->plaintext,
				'author' => $issue->find('.desc a', 0)->plaintext,
				'timestamp' => $issue->find('.time-since', 0)->title,
				'uid' => $issue->find('.label', 0)->plaintext,
			);

			if($this->getInput('include_description')) {
				$issue_html = getSimpleHTMLDOMCached($uri, 3600)
					or returnServerError('Unable to load issue description');

				$issue_html = defaultLinkTo($issue_html, $uri);

				$item['content'] = $issue_html->find('.comment .markdown', 0);
			}

			$this->items[] = $item;
		}
	}

	protected function collectSingleIssueData($html) {
		$comments = $html->find('.comments .comment')
			or returnServerError('Unable to find comments');

		foreach($comments as $comment) {
			$this->items[] = array(
				'uri' => $comment->find('a[href*="#issue"]', 0)->href,
				'title' => $comment->find('span', 0)->plaintext,
				'author' => $comment->find('.content a', 0)->plaintext,
				'timestamp' => $comment->find('.time-since', 0)->title,
				'content' => $comment->find('.markdown', 0),
			);
		}

		$this->items = array_reverse($this->items);
	}

	protected function collectReleasesData($html) {
		$releases = $html->find('#release-list li')
			or returnServerError('Unable to find releases');

		foreach($releases as $release) {
			$this->items[] = array(
				'uri' => $release->find('a', 0)->href,
				'title' => 'Release ' . $release->find('h4', 0)->plaintext,
			);
		}
	}
}
