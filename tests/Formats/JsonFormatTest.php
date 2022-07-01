<?php

/**
 * JsonFormat - JSON Feed Version 1
 * https://jsonfeed.org/version/1
 */

namespace RssBridge\Tests\Formats;

require_once __DIR__ . '/BaseFormatTest.php';

use PHPUnit\Framework\TestCase;

class JsonFormatTest extends BaseFormatTest
{
    private const PATH_EXPECTED = self::PATH_SAMPLES . 'expectedJsonFormat/';

    /**
     * @dataProvider sampleProvider
     * @runInSeparateProcess
     */
    public function testOutput(string $name, string $path)
    {
        $data = $this->formatData('Json', $this->loadSample($path));
        $this->assertNotNull(json_decode($data), 'invalid JSON output: ' . json_last_error_msg());

        $expected = self::PATH_EXPECTED . $name . '.json';
        $this->assertJsonStringEqualsJsonFile($expected, $data);
    }
}
