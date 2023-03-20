<?php

class InternationalInstituteForStrategicStudiesBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'International Institute For Strategic Studies Bridge';
    const URI = 'https://www.iiss.org';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns the latest blog posts from the IISS';

    const TEMPLATE_ID = '{6BCFD2C9-4F0B-4ACE-95D7-D14C8B60CD4D}';
    const COMPONENT_ID = '{E9850380-3707-43C9-994F-75ECE8048E04}';

    public function collectData()
    {
        $url = 'https://www.iiss.org/api/filter';
        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'templateId' => [self::TEMPLATE_ID],
                'componentId' => self::COMPONENT_ID,
                'page' => '1',
                'amount' => 10,
                'filter' => (object)[],
                'tags' => null,
                'sortType' => 'DateDesc',
                'restrictionType' => 'None'
            ])
        ];
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json;charset=UTF-8'
        ];
        $json = getContents($url, $headers, $opts);
        $data = json_decode($json);

        foreach ($data->model->Results as $record) {
            $this->items[] = [
                'uri' => self::URI . $record->Link,
                'title' => $record->Heading,
                'categories' => [$record->Topic],
                'author' => join(', ', array_map(function ($author) {
                    return $author->Name;
                }, $record->Authors)),
                'timestamp' => DateTime::createFromFormat('jS F Y', $record->Date)->format('U'),
                'content' => $this->getContents(self::URI . $record->Link)
            ];
        }
    }

    private function getContents($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);
        $body = $html->find('body', 0);
        $scripts = $body->find('script');
        $result = '';

        foreach ($scripts as $script) {
            $script_text = $script->innertext;
            if (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.Reading')) {
                $args = $this->getRenderArguments($script_text);
                $result .= $args->Html;
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.ImagePanel')) {
                $args = $this->getRenderArguments($script_text);
                $image_tag = str_replace('src="/-', 'src="' . self::URI . '/-', $args->Image);
                $result .= '<figure>' . $image_tag . '</figure>';
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.Intro')) {
                $args = $this->getRenderArguments($script_text);
                $result .= '<p>' . $args->Intro . '</p>';
            }
        }
        return $result;
    }

    private function getRenderArguments($script_text)
    {
        $matches = [];
        preg_match('/React\.createElement\(Components\.\w+, {(.*)}\),/', $script_text, $matches);
        return json_decode('{' . $matches[1] . '}');
    }
}
