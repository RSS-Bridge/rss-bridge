<?php

class OneNewThingBridge extends BridgeAbstract
{
    const NAME = 'OneNewThingBridge';
    const URI = 'https://onenewthing.net';
    const DESCRIPTION = 'A paid daily newsletter that provides insights on computer science, available for free one day later';
    const MAINTAINER = 'tillcash';

    public function collectData()
    {
        $url = 'https://usn5fspycxyyn6nrpzulttajxq0fpjtb.lambda-url.ap-southeast-1.on.aws';
        $jsonString = getContents($url);
        $jsonData = json_decode($jsonString);

        if (!$jsonData || !isset($jsonData->topic->content->S)) {
            returnServerError('Failed to retrieve valid JSON data');
        }

        $data = json_decode($jsonData->topic->content->S);

        // Constructing the content in an array
        $contentArray = [];
        $contentArray[] = "<h3>Introduction</h3><p>$data->introduction</p>";
        $contentArray[] = "<h3>Overview</h3><p>$data->overview</p>";
        $contentArray[] = "<h3>Importance</h3><p>$data->importance</p>";
        $contentArray[] = "<h3>Historical Context</h3><p>$data->historicalContext</p>";
        $contentArray[] = '<h3>Use Cases</h3><ul><li>' . implode('</li><li>', $data->useCases) . '</li></ul>';
        $contentArray[] = '<h3>Pros</h3><ul><li>' . implode('</li><li>', $data->pros) . '</li></ul>';
        $contentArray[] = '<h3>Cons</h3><ul><li>' . implode('</li><li>', $data->cons) . '</li></ul>';
        $contentArray[] = '<h3>Key Takeaways</h3><ul><li>' . implode('</li><li>', $data->keyTakeaways) . '</li></ul>';

        // Concatenate array elements into a single string
        $content = implode('', $contentArray);

        // Assigning to items
        $this->items[] = [
            'title' => $data->title,
            'content' => $content,
            'url' => self::URI,
            'uid' => $jsonData->topic->id->S,
        ];
    }
}
