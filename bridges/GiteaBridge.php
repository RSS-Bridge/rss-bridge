<?php
/**
 * Gitea is a fork of Gogs which may diverge in the future.
 * https://docs.gitea.io/en-us/
 */
require_once 'GogsBridge.php';

class GiteaBridge extends GogsBridge {

	const NAME = 'Gitea';
	const URI = 'https://gitea.io';
	const DESCRIPTION = 'Returns the latest issues, commits or releases';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 300; // 5 minutes

	const PARAMETERS = array(
		'global' => array(
			'host' => array(
				'name' => 'Host',
				'exampleValue' => 'https://notabug.org',
				'required' => true,
				'title' => 'Host name with its protocol, without trailing slash',
			),
			'user' => array(
				'name' => 'Username',
				'exampleValue' => 'PDModdingCommunity',
				'required' => true,
				'title' => 'User name as it appears in the URL',
			),
			'project' => array(
				'name' => 'Project name',
				'exampleValue' => 'PD-Loader',
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
				'exampleValue' => 100,
				'required' => true,
				'title' => 'Issue number from the issues list',
			),
		),
		'Releases' => array(),
		'Tags' => array(),
	);

	public function getName() {
		switch($this->queriedContext) {
			case 'Tags': return 'Tags for ' . $this->title;
			default: return parent::getName();
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'Tags': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/tags/';
			} break;
			default: return parent::getURI();
		}
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request ' . $this->getURI());
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
			case 'Tags': {
				$this->collectTagsData($html);
			} break;
		}
	}

	protected function collectReleasesData($html) {
		$releases = $html->find('#release-list > li')
			or returnServerError('Unable to find releases');

		foreach($releases as $release) {
			$this->items[] = array(
				'uri' => $release->find('a', 0)->href,
				'title' => 'Release ' . $release->find('h3', 0)->plaintext,
			);
		}
	}

	protected function collectTagsData($html) {
		$tags = $html->find('table#tags-table > tbody > tr')
			or returnServerError('Unable to find tags');

		foreach($tags as $tag) {
			$this->items[] = array(
				'uri' => $tag->find('a', 0)->href,
				'title' => 'Tag ' . $tag->find('.release-tag-name > a', 0)->plaintext,
			);
		}
	}
}
