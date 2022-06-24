<?php

class CachetBridge extends BridgeAbstract
{
    const NAME = 'Cachet Bridge';
    const URI = 'https://cachethq.io/';
    const DESCRIPTION = 'Returns status updates from any Cachet installation';
    const MAINTAINER  = 'klimplant';
    const PARAMETERS = array(
        array(
            'host' => array(
                'name' => 'Cachet installation',
                'type' => 'text',
                'required' => true,
                'title' => 'The URL of the Cachet installation',
                'exampleValue' => 'https://demo.cachethq.io/',
            ), 'additional_info' => array(
                'name' => 'Additional Timestamps',
                'type' => 'checkbox',
                'title' => 'Whether to include the given timestamps'
            )
        )
    );
    const CACHE_TIMEOUT = 300;

    private $componentCache = array();

    public function getURI()
    {
        return $this->getInput('host') === null ? 'https://cachethq.io/' : $this->getInput('host');
    }

    /**
     * Validates the ping request to the cache API
     *
     * @param string $ping
     * @return boolean
     */
    private function validatePing($ping)
    {
        $ping = json_decode($ping);
        if ($ping === null) {
            return false;
        }
        return $ping->data === 'Pong!';
    }

    /**
     * Returns the component name of a cachat component
     *
     * @param integer $id
     * @return string
     */
    private function getComponentName($id)
    {
        if ($id === 0) {
            return '';
        }
        if (array_key_exists($id, $this->componentCache)) {
            return $this->componentCache[$id];
        }

        $component = getContents($this->getURI() . '/api/v1/components/' . $id);
        $component = json_decode($component);
        if ($component === null) {
            return '';
        }
        return $component->data->name;
    }

    public function collectData()
    {
        $ping = getContents(urljoin($this->getURI(), '/api/v1/ping'));
        if (!$this->validatePing($ping)) {
            returnClientError('Provided URI is invalid!');
        }

        $url = urljoin($this->getURI(), '/api/v1/incidents?sort=id&order=desc');
        $incidents = getContents($url);
        $incidents = json_decode($incidents);
        if ($incidents === null) {
            returnClientError('/api/v1/incidents returned no valid json');
        }

        usort($incidents->data, function ($a, $b) {
            $timeA = strtotime($a->updated_at);
            $timeB = strtotime($b->updated_at);
            return $timeA > $timeB ? -1 : 1;
        });

        foreach ($incidents->data as $incident) {
            if (isset($incident->permalink)) {
                $permalink = $incident->permalink;
            } else {
                $permalink = urljoin($this->getURI(), '/incident/' . $incident->id);
            }

            $title = $incident->human_status . ': ' . $incident->name;
            $message = '';
            if ($this->getInput('additional_info')) {
                if (isset($incident->occurred_at)) {
                    $message .= 'Occurred at: ' . $incident->occurred_at . "\r\n";
                }
                if (isset($incident->scheduled_at)) {
                    $message .= 'Scheduled at: ' . $incident->scheduled_at . "\r\n";
                }
                if (isset($incident->created_at)) {
                    $message .= 'Created at: ' . $incident->created_at . "\r\n";
                }
                if (isset($incident->updated_at)) {
                    $message .= 'Updated at: ' . $incident->updated_at . "\r\n\r\n";
                }
            }

            $message .= $incident->message;
            $content = nl2br($message);
            $componentName = $this->getComponentName($incident->component_id);
            $uidOrig = $permalink . $incident->created_at;
            $uid = hash('sha512', $uidOrig);
            $timestamp = strtotime($incident->created_at);
            $categories = array();
            $categories[] = $incident->human_status;
            if ($componentName !== '') {
                $categories[] = $componentName;
            }

            $item = array();
            $item['uri'] = $permalink;
            $item['title'] = $title;
            $item['timestamp'] = $timestamp;
            $item['content'] = $content;
            $item['uid'] = $uid;
            $item['categories'] = $categories;

            $this->items[] = $item;
        }
    }
}
