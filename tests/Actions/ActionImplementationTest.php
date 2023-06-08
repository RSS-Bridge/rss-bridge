<?php

namespace RssBridge\Tests\Actions;

use ActionInterface;
use PHPUnit\Framework\TestCase;

class ActionImplementationTest extends TestCase
{
    private $class;
    private $obj;

    public function setUp(): void
    {
        \Configuration::loadConfiguration();
    }

    /**
     * @dataProvider dataActionsProvider
     */
    public function testClassName($path)
    {
        $this->setAction($path);
        $this->assertTrue($this->class === ucfirst($this->class), 'class name must start with uppercase character');
        $this->assertEquals(0, substr_count($this->class, ' '), 'class name must not contain spaces');
        $this->assertStringEndsWith('Action', $this->class, 'class name must end with "Action"');
    }

    /**
     * @dataProvider dataActionsProvider
     */
    public function testClassType($path)
    {
        $this->setAction($path);
        $this->assertInstanceOf(ActionInterface::class, $this->obj);
    }

    /**
     * @dataProvider dataActionsProvider
     */
    public function testVisibleMethods($path)
    {
        $allowedMethods = get_class_methods(ActionInterface::class);
        sort($allowedMethods);

        $this->setAction($path);

        $methods = array_diff(get_class_methods($this->obj), ['__construct']);
        sort($methods);

        $this->assertEquals($allowedMethods, $methods);
    }

    public function dataActionsProvider()
    {
        $actions = [];
        foreach (glob(PATH_LIB_ACTIONS . '*.php') as $path) {
            $actions[basename($path, '.php')] = [$path];
        }
        return $actions;
    }

    private function setAction($path)
    {
        $this->class = '\\' . basename($path, '.php');
        $this->assertTrue(class_exists($this->class), 'class ' . $this->class . ' doesn\'t exist');
        $this->obj = new $this->class();
    }
}
