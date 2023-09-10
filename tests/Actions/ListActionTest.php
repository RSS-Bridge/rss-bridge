<?php

namespace RssBridge\Tests\Actions;

use BridgeFactory;
use PHPUnit\Framework\TestCase;

class ListActionTest extends TestCase
{
    public function setUp(): void
    {
        \Configuration::loadConfiguration();
    }

    public function testHeaders()
    {
        $action = new \ListAction();
        $response = $action->execute([]);
        $headers = $response->getHeaders();
        $contentType = $response->getHeader('content-type');
        $this->assertSame($contentType, 'application/json');
    }

    public function testOutput()
    {
        $action = new \ListAction();
        $response = $action->execute([]);
        $data = $response->getBody();

        $items = json_decode($data, true);

        $this->assertNotNull($items, 'invalid JSON output: ' . json_last_error_msg());

        $this->assertArrayHasKey('total', $items, 'Missing "total" parameter');
        $this->assertIsInt($items['total'], 'Invalid type');

        $this->assertArrayHasKey('bridges', $items, 'Missing "bridges" array');

        $this->assertEquals(
            $items['total'],
            count($items['bridges']),
            'Item count doesn\'t match'
        );

        $bridgeFactory = new BridgeFactory();

        $this->assertEquals(
            count($bridgeFactory->getBridgeClassNames()),
            count($items['bridges']),
            'Number of bridges doesn\'t match'
        );

        $expectedKeys = [
            'status',
            'uri',
            'name',
            'icon',
            'parameters',
            'maintainer',
            'description'
        ];

        $allowedStatus = [
            'active',
            'inactive'
        ];

        foreach ($items['bridges'] as $bridge) {
            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $bridge, 'Missing key "' . $key . '"');
            }

            $this->assertContains($bridge['status'], $allowedStatus, 'Invalid status value');
        }
    }
}
