<?php

/**
 * MrssFormat - RSS 2.0 + Media RSS
 * http://www.rssboard.org/rss-specification
 * http://www.rssboard.org/media-rss
 */

namespace RssBridge\Tests\Formats;

require_once __DIR__ . '/BaseFormatTest.php';

use PHPUnit\Framework\TestCase;

class MrssFormatTest extends BaseFormatTest
{
    private const PATH_EXPECTED = self::PATH_SAMPLES . 'expectedMrssFormat/';

    /**
     * @dataProvider sampleProvider
     * @runInSeparateProcess
     */
    public function testOutput(string $name, string $path)
    {
        $data = $this->formatData('Mrss', $this->loadSample($path));
        $this->assertNotFalse(simplexml_load_string($data));

        $expected = self::PATH_EXPECTED . $name . '.xml';
        $this->assertXmlStringEqualsXmlFile($expected, $data);
    }
}
