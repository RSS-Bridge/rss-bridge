<?php

namespace RssBridge\Tests;

use BridgeAbstract;
use FeedExpander;
use PHPUnit\Framework\TestCase;

class BridgeImplementationTest extends TestCase
{
    private string $className;
    private BridgeAbstract $bridge;

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testClassName($path)
    {
        $this->setBridge($path);
        $this->assertTrue($this->className === ucfirst($this->className), 'class name must start with uppercase character');
        $this->assertEquals(0, substr_count($this->className, ' '), 'class name must not contain spaces');
        $this->assertStringEndsWith('Bridge', $this->className, 'class name must end with "Bridge"');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testClassType($path)
    {
        $this->setBridge($path);
        $this->assertInstanceOf(BridgeAbstract::class, $this->bridge);
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testConstants($path)
    {
        $this->setBridge($path);

        $this->assertIsString($this->bridge::NAME, 'class::NAME');
        $this->assertNotEmpty($this->bridge::NAME, 'class::NAME');
        $this->assertIsString($this->bridge::URI, 'class::URI');
        $this->assertNotEmpty($this->bridge::URI, 'class::URI');
        $this->assertIsString($this->bridge::DESCRIPTION, 'class::DESCRIPTION');
        $this->assertNotEmpty($this->bridge::DESCRIPTION, 'class::DESCRIPTION');
        $this->assertIsString($this->bridge::MAINTAINER, 'class::MAINTAINER');
        $this->assertNotEmpty($this->bridge::MAINTAINER, 'class::MAINTAINER');

        $this->assertIsArray($this->bridge::PARAMETERS, 'class::PARAMETERS');
        $this->assertIsInt($this->bridge::CACHE_TIMEOUT, 'class::CACHE_TIMEOUT');
        $this->assertGreaterThanOrEqual(0, $this->bridge::CACHE_TIMEOUT, 'class::CACHE_TIMEOUT');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testParameters($path)
    {
        $this->setBridge($path);

        $multiMinimum = 2;
        if (isset($this->bridge::PARAMETERS['global'])) {
            ++$multiMinimum;
        }
        $multiContexts = (count($this->bridge::PARAMETERS) >= $multiMinimum);
        $paramsSeen = [];

        $allowedTypes = [
            'text',
            'number',
            'list',
            'checkbox',
        ];

        foreach ($this->bridge::PARAMETERS as $context => $params) {
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

        foreach ($this->bridge::TEST_DETECT_PARAMETERS as $url => $params) {
            $detectedParameters = $this->bridge->detectParameters($url);
            $this->assertEquals($detectedParameters, $params);
        }
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testVisibleMethods($path)
    {
        $bridgeAbstractMethods = get_class_methods(BridgeAbstract::class);
        sort($bridgeAbstractMethods);
        $feedExpanderMethods = get_class_methods(FeedExpander::class);
        sort($feedExpanderMethods);

        $this->setBridge($path);

        $publicMethods = get_class_methods($this->bridge);
        sort($publicMethods);
        foreach ($publicMethods as $publicMethod) {
            if ($this->bridge instanceof FeedExpander) {
                $this->assertContains($publicMethod, $feedExpanderMethods);
            } else {
                $this->assertContains($publicMethod, $bridgeAbstractMethods);
            }
        }
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testMethodValues($path)
    {
        $this->setBridge($path);

        $value = $this->bridge->getDescription();
        $this->assertIsString($value, '$class->getDescription()');
        $this->assertNotEmpty($value, '$class->getDescription()');

        $value = $this->bridge->getMaintainer();
        $this->assertIsString($value, '$class->getMaintainer()');
        $this->assertNotEmpty($value, '$class->getMaintainer()');

        $value = $this->bridge->getName();
        $this->assertIsString($value, '$class->getName()');
        $this->assertNotEmpty($value, '$class->getName()');

        $value = $this->bridge->getURI();
        $this->assertIsString($value, '$class->getURI()');
        $this->assertNotEmpty($value, '$class->getURI()');

        $value = $this->bridge->getIcon();
        $this->assertIsString($value, '$class->getIcon()');
    }

    /**
     * @dataProvider dataBridgesProvider
     */
    public function testUri($path)
    {
        $this->setBridge($path);

        $this->assertNotFalse(filter_var($this->bridge::URI, FILTER_VALIDATE_URL));
        $this->assertNotFalse(filter_var($this->bridge->getURI(), FILTER_VALIDATE_URL));
    }

    public function dataBridgesProvider()
    {
        $bridges = [];
        foreach (glob(__DIR__ . '/../bridges/*Bridge.php') as $path) {
            $bridges[basename($path, '.php')] = [$path];
        }
        return $bridges;
    }

    private function setBridge($path)
    {
        $this->className = '\\' . basename($path, '.php');
        $this->assertTrue(class_exists($this->className), 'class ' . $this->className . ' doesn\'t exist');
        $this->bridge = new $this->className(
            new \NullCache(),
            new \NullLogger(),
        );
    }
}
