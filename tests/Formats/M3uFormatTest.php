<?php

/**
 * M3uFormat
 */

namespace RssBridge\Tests\Formats;

require_once __DIR__ . '/BaseFormatTest.php';

use PHPUnit\Framework\TestCase;

class M3uFormatTest extends BaseFormatTest
{
    private const PATH_EXPECTED = self::PATH_SAMPLES . 'expectedM3uFormat/';

    /**
     * @dataProvider sampleProvider
     * @runInSeparateProcess
     */
    public function testOutput(string $name, string $path)
    {
        $data = $this->formatData('M3u', $this->loadSample($path));

        $expected = file_get_contents(self::PATH_EXPECTED . $name . '.m3u');
        $this->assertEquals($expected, $data);
    }
}

