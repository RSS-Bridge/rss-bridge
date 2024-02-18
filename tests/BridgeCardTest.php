<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use BridgeCard;
use PHPUnit\Framework\TestCase;

class BridgeCardTest extends TestCase
{
    public function test()
    {
        $sut = new BridgeCard();
        $this->assertSame('', BridgeCard::getInputAttributes([]));
        $this->assertSame(' required pattern="\d+"', BridgeCard::getInputAttributes(['required' => true, 'pattern' => '\d+']));

        $entry = [
            'defaultValue' => 'checked',
        ];
        $this->assertSame('<input  id="id" type="checkbox" name="name" checked />' . "\n", BridgeCard::getCheckboxInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 42,
            'exampleValue' => 43,
        ];
        $this->assertSame('<input  id="id" type="number" value="42" placeholder="43" name="name" />' . "\n", BridgeCard::getNumberInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 'yo1',
            'exampleValue' => 'yo2',
        ];
        $this->assertSame('<input  id="id" type="text" value="yo1" placeholder="yo2" name="name" />', BridgeCard::getTextInput($entry, 'id', 'name'));

        $entry = [
            'values' => [],
        ];
        $this->assertSame('<select  id="id" name="name" >' . "\n" . '</select>', BridgeCard::getListInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 2,
            'values' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame('<select  id="id" name="name" >' . "\n" . '<option value="bar">foo</option>' . "\n" . '</select>', BridgeCard::getListInput($entry, 'id', 'name'));

        // optgroup
        $entry = [
            'defaultValue' => 2,
            'values' => ['kek' => [
                'f' => 'b',
            ]],
        ];
        $this->assertSame(
            '<select  id="id" name="name" >' . "\n" . '<optgroup label="kek"><option value="b">f</option></optgroup></select>',
            BridgeCard::getListInput($entry, 'id', 'name')
        );
    }
}
