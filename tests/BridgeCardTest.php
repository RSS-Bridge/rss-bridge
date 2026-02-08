<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use BridgeCard;
use PHPUnit\Framework\TestCase;


class BridgeCardTest extends TestCase
{
    public function test()
    {
        $entry = [
            'defaultValue' => 'checked',
        ];
        $this->assertSame('<input id="id" type="checkbox" name="name" checked />', BridgeCard::getCheckboxInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 42,
            'exampleValue' => 43,
        ];
        $this->assertSame('<input id="id" type="number" value="42" placeholder="43" name="name" />', BridgeCard::getNumberInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 'yo1',
            'exampleValue' => 'yo2',
        ];
        $this->assertSame('<input id="id" type="text" value="yo1" placeholder="yo2" name="name" />', BridgeCard::getTextInput($entry, 'id', 'name'));

        $entry = [
            'values' => [],
        ];
        $this->assertSame('<select id="id" name="name" >' . "\n" . '</select>', BridgeCard::getListInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 2,
            'values' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame('<select id="id" name="name" >' . "\n" . '<option value="bar">foo</option>' . "\n". '</select>', BridgeCard::getListInput($entry, 'id', 'name'));

        // optgroup
        $entry = [
            'defaultValue' => 2,
            'values' => ['kek' => [
                'f' => 'b',
            ]],
        ];
        $this->assertSame(
            '<select id="id" name="name" >' . "\n" . '<optgroup label="kek"><option value="b">f</option>'."\n".'</optgroup></select>',
            BridgeCard::getListInput($entry, 'id', 'name')
        );
    }

    public function test2()
    {
        $this->assertSame('<input />', html_input([
        ]));

        $this->assertSame('<input type="text" />', html_input([
            'type' => 'text',
        ]));

        $this->assertSame('<input type="text" required />', html_input([
            'type'      => 'text',
            'required'  => true,
        ]));

        $this->assertSame('<input type="text" />', html_input([
            'type'      => 'text',
            'required'  => false,
        ]));

        $this->assertSame('<input type="text" id="id" name="name" value="val" placeholder="placeholder" pattern="\d\d" checked required />', html_input([
            'type'          => 'text',
            'id'            => 'id',
            'name'          => 'name',
            'value'         => 'val',
            'placeholder'   => 'placeholder',
            'pattern'       => '\d\d',
            'checked'       => true,
            'required'      => true,
        ]));

        // test self-closing
        $this->assertSame('<p >', html_tag('p', [], false));
        $this->assertSame('<p />', html_tag('p', [], true));

        // test escaping
        $this->assertSame('<input type="text" value="&lt;h1&gt;" />', html_input([
            'type'  => 'text',
            'value' => '<h1>'
        ]));

        // test option
        $this->assertSame('<option value="val">name</option>', html_option('name', 'val'));
        $this->assertSame('<option value="val">name</option>', html_option('name', 'val', false));
        $this->assertSame('<option value="val" selected>name</option>', html_option('name', 'val', true));
    }
}
