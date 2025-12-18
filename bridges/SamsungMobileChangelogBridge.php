<?PHP

class SamsungMobileChangelogBridge extends BridgeAbstract
{
    const NAME = 'Samsung Mobile Changelog';
    const URI = 'https://doc.samsungmobile.com/';
    const DESCRIPTION = 'Returns the changelog of selected devices from the Samsung Mobile documentation in English';
    const MAINTAINER = 'ajain-93';
    const PARAMETERS = [
        [
            'devices' => [
                'name' => 'Device Model',
                'required' => true,
                'exampleValue' => 'SM-S931B',
            ],
            'region' => [
                'name' => 'Region',
                'required' => true,
                'exampleValue' => 'EUX',
            ],
        ]
    ];
    private $device = '';

    const STR_BUILD_NUMBER = 'Build Number';
    const STR_ANDROID_VERSION = 'Android version';
    const STR_RELEASE_DATE = 'Release Date';
    const STR_SECURITY_PATCH_LEVEL = 'Security patch level';


    public function collectData()
    {
        $URL = self::URI . $this->getInput('devices') . '/' . $this->getInput('region') . '/doc.html';

        $html = getSimpleHTMLDOMCached($URL)
            or throwServerException('Could not request changelog page: ' . $URL);
        $changelog = self::URI . $this->getInput('devices') . '/' . $this->getInput('region') . '/' . $html->find('input', 0)->value;

        $html = getSimpleHTMLDOMCached($changelog)
            or throwServerException('Could not request changelog: ' . $changelog);
        $container = $html->find('div.container', 0);
        $this->device = trim($html->find('h1', 0)->plaintext);
        // Debug::log('Device: ' . $device);

        $reachedStart = false;
        foreach ($container->children() as $element) {

            if ($element->tag == 'hr') {
                $reachedStart = true;
                $item = array();
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
                $item['author'] = 'Samsung Mobile';
                $this->items[] = $item;

                // break;
                continue;
            }
        }
    }

    public function getName()
    {
        return htmlspecialchars_decode($this->device) . ' - ' . self::NAME;
    }
}
