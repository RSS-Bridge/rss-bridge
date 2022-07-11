<?php

final class GabBridge extends BridgeAbstract
{
    const NAME = 'Gab';
    const URI = 'https://gab.com/';
    const DESCRIPTION = 'Gab is an American alt-tech microblogging and social networking service';
    const MAINTAINER = 'dvikan';
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'type' => 'text',
                'defaultValue' => 'realdonaldtrump',
            ],
        ]
    ];

    public function collectData()
    {
        $username = $this->getUsername();
        $response = json_decode(getContents(sprintf('https://gab.com/api/v1/account_by_username/%s', $username)));
        $id = $response->id;
        $gabs = json_decode(getContents(sprintf('https://gab.com/api/v1/accounts/%s/statuses?exclude_replies=true', $id)));
        foreach ($gabs as $gab) {
            if ($gab->reblog) {
                continue;
            }
            $this->items[] = [
                'title' => $gab->content ?: 'Untitled',
                'author' => $username,
                'uri' => $gab->url ?? sprintf('https://gab.com/%s', $username),
                'content' => $gab->content,
                'timestamp' => (new \DateTime($gab->created_at))->getTimestamp(),
            ];
        }
    }

    public function getName()
    {
        return 'Gab - ' . $this->getUsername();
    }

    public function getURI()
    {
        return 'https://gab.com/' . $this->getUsername();
    }

    private function getUsername(): string
    {
        $username = ltrim($this->getInput('username') ?? '', '@');
        if (preg_match('#https?://gab\.com/(\w+)#', $username, $m)) {
            return $m[1];
        }
        return $username;
    }
}
