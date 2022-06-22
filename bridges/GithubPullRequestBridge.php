<?php

class GitHubPullRequestBridge extends GithubIssueBridge {
	const NAME = 'GitHub Pull Request';
	const DESCRIPTION = 'Returns the pull request or comments of a pull request of a GitHub project';

	const PARAMETERS = array(
		'global' => array(
			'u' => array(
				'name' => 'User name',
				'exampleValue' => 'RSS-Bridge',
				'required' => true
			),
			'p' => array(
				'name' => 'Project name',
				'exampleValue' => 'rss-bridge',
				'required' => true
			)
		),
		'Project Pull Requests' => array(
			'c' => array(
				'name' => 'Show Pull Request Comments',
				'type' => 'checkbox'
			),
			'q' => array(
				'name' => 'Search Query',
				'defaultValue' => 'is:pr is:open sort:created-desc',
				'required' => true
			)
		),
		'Pull Request comments' => array(
			'i' => array(
				'name' => 'Pull Request number',
				'type' => 'number',
				'exampleValue' => '2100',
				'required' => true
			)
		)
	);

	const BRIDGE_OPTIONS = array(0 => 'Project Pull Requests', 1 => 'Pull Request comments');
	const URL_PATH = 'pull';
	const SEARCH_QUERY_PATH = 'pulls';
}
