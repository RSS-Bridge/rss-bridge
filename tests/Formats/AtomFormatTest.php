<?php

/**
 * AtomFormat - RFC 4287: The Atom Syndication Format
 * https://tools.ietf.org/html/rfc4287
 */

namespace RssBridge\Tests\Formats;

require_once __DIR__ . '/BaseFormatTest.php';

use PHPUnit\Framework\TestCase;

class AtomFormatTest extends BaseFormatTest
{
    private const PATH_EXPECTED = self::PATH_SAMPLES . 'expectedAtomFormat/';

    /**
     * @dataProvider sampleProvider
     * @runInSeparateProcess
     */
    public function testOutput(string $name, string $path)
    {
        $data = $this->formatData('Atom', $this->loadSample($path));
        $this->assertNotFalse(simplexml_load_string($data));

        $expected = self::PATH_EXPECTED . $name . '.xml';
        $this->assertXmlStringEqualsXmlFile($expected, $data);
    }
}
