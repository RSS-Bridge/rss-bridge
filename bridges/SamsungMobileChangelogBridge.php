<?PHP

class SamsungMobileChangelogBridge extends BridgeAbstract
{
    const NAME = 'Samsung Mobile Changelog';
    const URI = 'https://doc.samsungmobile.com/';
    const DESCRIPTION = 'Changelog of selected device from the Samsung Mobile documentation in English';
    const MAINTAINER = 'ajain-93';
    const PARAMETERS = [
        [
            'device' => [
                'name' => 'Device Model',
                'title' => 'The model name found in Settings → About phone/tablet\nSM-931B/DS → SM-S931B',
                'required' => true,
                'exampleValue' => 'SM-S931B',
            ],
            'region' => [
                'name' => 'Region',
                'title' => 'The 3 letter region code found in Service provider software version in\nSettings → About phone/tablet → Software information',
                'required' => true,
                'exampleValue' => 'EUX',
            ],
        ]
    ];
    private $device_name = '';

    const STR_BUILD_NUMBER = 'Build Number';
    const STR_ANDROID_VERSION = 'Android version';
    const STR_RELEASE_DATE = 'Release Date';
    const STR_SECURITY_PATCH_LEVEL = 'Security patch level';


    public function collectData()
    {
        $URL = $this->getURI();

        $html = getSimpleHTMLDOMCached($URL)
            or throwServerException('Could not request changelog page: ' . $URL);

        // Iterate through language options, and find the English version
        $url_language = $this->getBaseURL() . $html->find('option[value=EN]', 0)->plaintext;
        if (!isset($url_language)) {
            throwServerException('Unable to find English version');
        }

        $html = getSimpleHTMLDOMCached($url_language)
            or throwServerException('Could not request changelog: ' . $url_language);
        $container = $html->find('div.container', 0);
        $this->device_name = trim($html->find('h1', 0)->plaintext);
        // Debug::log('Device: ' . $device);

        $reachedStart = false;
        foreach ($container->children() as $element) {
            if ($element->tag == 'hr') {
                $reachedStart = true;
                $item = [];
                continue;
            } else if (!$reachedStart) {
                // Skip non-changelog elements
                continue;
            } else if ($element->tag == 'div' && $element->getAttribute('class') == 'row') {
                // Debug::log('Processing row element');
                $build = $element->find('div', 0)->plaintext;
                $build = str_replace(self::STR_BUILD_NUMBER . ' : ', '', $build);

                $version = $element->find('div', 1)->plaintext;
                $version = str_replace(self::STR_ANDROID_VERSION . ' : ', '', $version);

                $date = $element->find('div', 2)->plaintext;
                $date = str_replace(self::STR_RELEASE_DATE . ' : ', '', $date);

                $patch = $element->find('div', 3)->plaintext;
                $patch = str_replace(self::STR_SECURITY_PATCH_LEVEL . ' : ', '', $patch);

                $item['title'] = $date . ' ' . $build;
                $item['uri'] = $URL;
                $item['timestamp'] = strtotime($date);
                $item['content'] = '<b>' . self::STR_BUILD_NUMBER . ':</b> ' . $build . '<br>';
                $item['content'] .= '<b>' . self::STR_ANDROID_VERSION . ':</b> ' . $version . '<br>';
                $item['content'] .= '<b>' . self::STR_RELEASE_DATE . ':</b> ' . $date . '<br>';
                $item['content'] .= '<b>' . self::STR_SECURITY_PATCH_LEVEL . ':</b> ' . $patch . '<br>';
                $item['content'] .= '<br><b>Changelog: </b><br>';

                continue;
            } else {
                $item['content'] .= $element;
                $this->items[] = $item;

                // break;
                continue;
            }
        }
    }


    private function getBaseURL()
    {
        return self::URI . $this->getInput('device') . '/' . $this->getInput('region') . '/';
    }

    public function getURI()
    {
        if ($this->getInput('device')) {
            return $this->getBaseURL() . 'doc.html';
        } else {
            return self::URI;
        }
    }

    public function getName()
    {
        if ($this->device_name) {
            return htmlspecialchars_decode($this->device_name) . ' - Changelog';
        } else {
            return self::NAME;
        }
    }
}
