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
            'values' => [],
        ];
        $this->assertSame('<select id="id" name="name">' . "\n" . '</select>' . "\n", BridgeCard::getListInput($entry, 'id', 'name'));

        $entry = [
            'defaultValue' => 2,
            'values' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame('<select id="id" name="name">' . "\n" . '<option value="bar">foo</option>' . "\n" . '</select>' . "\n", BridgeCard::getListInput($entry, 'id', 'name'));

        // optgroup
        $entry = [
            'defaultValue' => 2,
            'values' => ['kek' => [
                'f' => 'b',
            ]],
        ];
        $this->assertSame(
            '<select id="id" name="name">' . "\n" . '<optgroup label="kek"><option value="b">f</option>' . "\n" . '</optgroup></select>' . "\n",
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
        $this->assertSame('<p />', html_tag('p'));
        $this->assertSame('<p>hello</p>', html_tag('p', 'hello'));
        $this->assertSame('<option value="AAA">QQQ</option>', html_tag('option', 'QQQ', ['value' => 'AAA']));
        $this->assertSame('<p class="red">hello</p>', html_tag('p', 'hello', ['class' => 'red']));

        // test escaping
        $this->assertSame('<input type="text" value="&lt;h1&gt;" />', html_input([
            'type'  => 'text',
            'value' => '<h1>'
        ]));

        // test option
        $this->assertSame('<option value="val">name</option>', html_option('name', 'val'));
        $this->assertSame('<option value="val">name</option>', html_option('name', 'val', false));
        $this->assertSame('<option value="val" selected>name</option>', html_option('name', 'val', true));

        // test label
        $this->assertSame('<label class="showless" for="for2">Show less</label>', html_tag('label', 'Show less', [
            'class' => 'showless',
            'for' => 'for2',
        ]));
    }
}
