<?php

namespace RssBridge\Tests\Formats;

use PHPUnit\Framework\TestCase;
use FormatFactory;

abstract class BaseFormatTest extends TestCase
{
    protected const PATH_SAMPLES = __DIR__ . '/samples/';

    /**
     * @return array<string, array{string, string}>
     */
    public function sampleProvider()
    {
        $samples = [];
        foreach (glob(self::PATH_SAMPLES . '*.json') as $path) {
            $name = basename($path, '.json');
            $samples[$name] = [
                $name,
                $path,
            ];
        }
        return $samples;
    }

    /**
     * Cannot be part of the sample returned by sampleProvider since this modifies $_SERVER
     * and thus needs to be run in a separate process to avoid side effects.
     */
    protected function loadSample(string $path): \stdClass
    {
        $data = json_decode(file_get_contents($path), true);
        if (isset($data['meta']) && isset($data['items'])) {
            if (!empty($data['server'])) {
                $this->setServerVars($data['server']);
            }

            $items = [];
            foreach ($data['items'] as $item) {
                $items[] = \FeedItem::fromArray($item);
            }

            return (object)[
                'meta' => $data['meta'],
                'items' => $items,
            ];
        } else {
            $this->fail('invalid test sample: ' . basename($path, '.json'));
        }
    }

    private function setServerVars(array $list): void
    {
        $_SERVER = array_merge($_SERVER, $list);
    }

    protected function formatData(string $formatName, \stdClass $sample): string
    {
        $formatFactory = new FormatFactory();
        $format = $formatFactory->create($formatName);
        $format->setItems($sample->items);
        $format->setFeed($sample->meta);
        $format->setLastModified(strtotime('2000-01-01 12:00:00 UTC'));

        return $format->stringify(null);
    }
}
