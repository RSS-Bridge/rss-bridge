<?php

class InternationalInstituteForStrategicStudiesBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'International Institute For Strategic Studies Bridge';
    const URI = 'https://www.iiss.org';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns the latest blog posts from the IISS';

    const TEMPLATE_ID = ['BlogArticlePage', 'BlogPage'];
    const COMPONENT_ID = '9b0c6919-c78b-4910-9be9-d73e6ee40e50';

    public function collectData()
    {
        $url = 'https://www.iiss.org/api/filteredlist/filter';
        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'templateId' => self::TEMPLATE_ID,
                'componentId' => self::COMPONENT_ID,
                'page' => '1',
                'amount' => 1,
                'filter' => (object)[],
                'tags' => null,
                'sortType' => 'Newest',
                'restrictionType' => 'Any'
            ])
        ];
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json;charset=UTF-8',
        ];
        $json = getContents($url, $headers, $opts);
        $data = json_decode($json);

        foreach ($data->model->Results as $record) {
            [$content, $enclosures] = $this->getContents(self::URI . $record->Link);
            $this->items[] = [
                'uri' => self::URI . $record->Link,
                'title' => $record->Heading,
                'categories' => [$record->Topic],
                'author' => join(', ', array_map(function ($author) {
                    return $author->Name;
                }, $record->Authors)),
                'timestamp' => DateTime::createFromFormat('jS F Y', $record->Date)->format('U'),
                'content' => $content,
                'enclosures' => $enclosures
            ];
        }
    }

    private function getContents($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);
        $body = $html->find('body', 0);
        $scripts = $body->find('script');
        $result = '';

        $enclosures = [];

        foreach ($scripts as $script) {
            $script_text = $script->innertext;
            if (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.Reading')) {
                $args = $this->getRenderArguments($script_text);
                $result .= $args->Html;
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.ImagePanel')) {
                $args = $this->getRenderArguments($script_text);
                $result .= '<figure><img src="' . self::URI . $args->Image . '"></img></figure>';
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.Intro')) {
                $args = $this->getRenderArguments($script_text);
                $result .= '<p>' . $args->Intro . '</p>';
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.Footnotes')) {
                $args = $this->getRenderArguments($script_text);
                $result .= '<p>' . $args->Content . '</p>';
            } elseif (str_contains($script_text, 'ReactDOM.render(React.createElement(Components.List')) {
                $args = $this->getRenderArguments($script_text);
                foreach ($args->Items as $item) {
                    if ($item->Url != null) {
                        $match = preg_match('/\\"(.*)\\"/', $item->Url, $matches);
                        if ($match > 0) {
                            array_push($enclosures, self::URI . $matches[1]);
                        }
                    }
                }
            }
        }
        return [$result, $enclosures];
    }

    private function getRenderArguments($script_text)
    {
        $matches = [];
        preg_match('/React\.createElement\(Components\.\w+, {(.*)}\),/', $script_text, $matches);
        return json_decode('{' . $matches[1] . '}');
    }
}
