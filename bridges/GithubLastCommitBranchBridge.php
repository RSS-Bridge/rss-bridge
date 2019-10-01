<?php
class GithubLastCommitBranchBridge extends BridgeAbstract {

	const MAINTAINER = 'floviolleau';
	const NAME = 'Github Last commit of branch';
	const URI = 'https://api.github.com/repos/';
	const CACHE_TIMEOUT = 7200;
	const DESCRIPTION = 'Returns the last commit of a branch of a github project';

	const PARAMETERS = array(array(
        'u' => array(
            'name' => 'User name',
            'required' => true
        ),
        'p' => array(
            'name' => 'Project name',
            'required' => true
        ),
        'b' => array(
            'name' => 'Branch name',
            'defaultValue' => 'master'
        )
    ));

	public function getURI(){
		if(null !== $this->getInput('u') && null !== $this->getInput('p') && null !== $this->getInput('b')) {
			$uri = static::URI . $this->getInput('u') . '/'
				. $this->getInput('p') . '/commits/' . $this->getInput('b');

			return $uri;
		}

		return parent::getURI();
	}

	public function collectData(){
	    $url = $this->getURI();

		$header = array(
			'Content-Type: application/json',
		);

	    $opts = array(
            CURLOPT_SSL_VERIFYPEER => false
        );

	    $content = getContents($url, $header, $opts)
            or returnServerError('Could not request Github api . Tried: ' . $url);

        $commit = json_decode($content);

        $item['uri'] = $commit->commit->url;
        $item['title'] = $commit->commit->message;
        $item['author'] = $commit->commit->committer->name;
        $item['content'] = $commit->commit->message;
        $item['date'] = $commit->commit->committer->date;
        $item['uid'] = $commit->node_id;

        if (sizeof($commit->files) > 0) {
            $item['content'] .= "<br/>Files modified: <br/>";
        }

        foreach($commit->files as $file) {
            $item['content'] .= "    - $file->additions additions, $file->deletions deletions, $file->changes changes | $file->filename: <br/>";
        }

        $totalAdditions = $commit->stats->additions;
        $totalDeletions = $commit->stats->deletions;
        $item['content'] .= '</br>Total ' . count($commit->files) . " changed files with $totalAdditions additions and $totalDeletions deletions.";

        $this->items[] = $item;
	}
}
