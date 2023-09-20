<?php

namespace RssBridge\Tests\Bridges;

use BridgeAbstract;
use FeedExpander;
use PHPUnit\Framework\TestCase;

class BridgeImplementationTest extends TestCase
{
    private $class;
    private $obj;

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testClassName($path)
    {
        $this->setBridge($path);
        $this->assertTrue($this->class === ucfirst($this->class), 'class name must start with uppercase character');
        $this->assertEquals(0, substr_count($this->class, ' '), 'class name must not contain spaces');
        $this->assertStringEndsWith('Bridge', $this->class, 'class name must end with "Bridge"');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testClassType($path)
    {
        $this->setBridge($path);
        $this->assertInstanceOf(BridgeAbstract::class, $this->obj);
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testConstants($path)
    {
        $this->setBridge($path);

        $this->assertIsString($this->obj::NAME, 'class::NAME');
        $this->assertNotEmpty($this->obj::NAME, 'class::NAME');
        $this->assertIsString($this->obj::URI, 'class::URI');
        $this->assertNotEmpty($this->obj::URI, 'class::URI');
        $this->assertIsString($this->obj::DESCRIPTION, 'class::DESCRIPTION');
        $this->assertNotEmpty($this->obj::DESCRIPTION, 'class::DESCRIPTION');
        $this->assertIsString($this->obj::MAINTAINER, 'class::MAINTAINER');
        $this->assertNotEmpty($this->obj::MAINTAINER, 'class::MAINTAINER');

        $this->assertIsArray($this->obj::PARAMETERS, 'class::PARAMETERS');
        $this->assertIsInt($this->obj::CACHE_TIMEOUT, 'class::CACHE_TIMEOUT');
        $this->assertGreaterThanOrEqual(0, $this->obj::CACHE_TIMEOUT, 'class::CACHE_TIMEOUT');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testParameters($path)
    {
        $this->setBridge($path);

        $multiMinimum = 2;
        if (isset($this->obj::PARAMETERS['global'])) {
            ++$multiMinimum;
        }
        $multiContexts = (count($this->obj::PARAMETERS) >= $multiMinimum);
        $paramsSeen = [];

        $allowedTypes = [
            'text',
            'number',
            'list',
            'checkbox'
        ];

        foreach ($this->obj::PARAMETERS as $context => $params) {
            if ($multiContexts) {
                $this->assertIsString($context, 'invalid context name');

                $this->assertNotEmpty($context, 'The context name cannot be empty');
            }

            if (empty($params)) {
                continue;
            }

            foreach ($paramsSeen as $seen) {
                $this->assertNotEquals($seen, $params, 'same set of parameters not allowed');
            }
            $paramsSeen[] = $params;

            foreach ($params as $field => $options) {
                $this->assertIsString($field, $field . ': invalid id');
                $this->assertNotEmpty($field, $field . ':empty id');

                $this->assertIsString($options['name'], $field . ': invalid name');
                $this->assertNotEmpty($options['name'], $field . ': empty name');

                if (isset($options['type'])) {
                    $this->assertIsString($options['type'], $field . ': invalid type');
                    $this->assertContains($options['type'], $allowedTypes, $field . ': unknown type');

                    if ($options['type'] == 'list') {
                        $this->assertArrayHasKey('values', $options, $field . ': missing list values');
                        $this->assertIsArray($options['values'], $field . ': invalid list values');
                        $this->assertNotEmpty($options['values'], $field . ': empty list values');

                        foreach ($options['values'] as $valueName => $value) {
                            $this->assertIsString($valueName, $field . ': invalid value name');
                        }
                    }
                }

                if (isset($options['required'])) {
                    $this->assertIsBool($options['required'], $field . ': invalid required');

                    if ($options['required'] === true && isset($options['type'])) {
                        switch ($options['type']) {
                            case 'list':
                            case 'checkbox':
                                $this->assertArrayNotHasKey(
                                    'required',
                                    $options,
                                    $field . ': "required" attribute not supported for ' . $options['type']
                                );
                                break;
                        }
                    }
                }

                if (isset($options['title'])) {
                    $this->assertIsString($options['title'], $field . ': invalid title');
                    $this->assertNotEmpty($options['title'], $field . ': empty title');
                }

                if (isset($options['pattern'])) {
                    $this->assertIsString($options['pattern'], $field . ': invalid pattern');
                    $this->assertNotEquals('', $options['pattern'], $field . ': empty pattern');
                }

                if (isset($options['exampleValue'])) {
                    if (is_string($options['exampleValue'])) {
                        $this->assertNotEquals('', $options['exampleValue'], $field . ': empty exampleValue');
                    }
                }

                if (isset($options['defaultValue'])) {
                    if (is_string($options['defaultValue'])) {
                        $this->assertNotEquals('', $options['defaultValue'], $field . ': empty defaultValue');
                    }
                }
            }
        }

        foreach ($this->obj::TEST_DETECT_PARAMETERS as $url => $params) {
            $this->assertEquals($this->obj->detectParameters($url), $params);
        }

        $this->assertTrue(true);
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testVisibleMethods($path)
    {
        $allowedBridgeAbstract = get_class_methods(BridgeAbstract::class);
        sort($allowedBridgeAbstract);
        $allowedFeedExpander = get_class_methods(FeedExpander::class);
        sort($allowedFeedExpander);

        $this->setBridge($path);

        $methods = get_class_methods($this->obj);
        sort($methods);
        if ($this->obj instanceof FeedExpander) {
            $this->assertEquals($allowedFeedExpander, $methods);
        } else {
            $this->assertEquals($allowedBridgeAbstract, $methods);
        }
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testMethodValues($path)
    {
        $this->setBridge($path);

        $value = $this->obj->getDescription();
        $this->assertIsString($value, '$class->getDescription()');
        $this->assertNotEmpty($value, '$class->getDescription()');

        $value = $this->obj->getMaintainer();
        $this->assertIsString($value, '$class->getMaintainer()');
        $this->assertNotEmpty($value, '$class->getMaintainer()');

        $value = $this->obj->getName();
        $this->assertIsString($value, '$class->getName()');
        $this->assertNotEmpty($value, '$class->getName()');

        $value = $this->obj->getURI();
        $this->assertIsString($value, '$class->getURI()');
        $this->assertNotEmpty($value, '$class->getURI()');

        $value = $this->obj->getIcon();
        $this->assertIsString($value, '$class->getIcon()');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testUri($path)
    {
        $this->setBridge($path);

        $this->checkUrl($this->obj::URI);
        $this->checkUrl($this->obj->getURI());
    }

    public function dataBridgesProvider()
    {
        $bridges = [];
        foreach (glob(__DIR__ . '/../../bridges/*Bridge.php') as $path) {
            $bridges[basename($path, '.php')] = [$path];
        }
        return $bridges;
    }

    private function setBridge($path)
    {
        $this->class = '\\' . basename($path, '.php');
        $this->assertTrue(class_exists($this->class), 'class ' . $this->class . ' doesn\'t exist');
        $this->obj = new $this->class(
            new \NullCache(),
            new \NullLogger()
        );
    }

    private function checkUrl($url)
    {
        $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL), 'no valid URL: ' . $url);
    }
}
