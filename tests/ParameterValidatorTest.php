<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class ParameterValidatorTest extends TestCase
{
    public function test1()
    {
        $sut = new \ParameterValidator();
        $input = ['user' => 'joe'];
        $parameters = [
            [
                'user' => [
                    'name' => 'User',
                    'type' => 'text',
                ],
            ]
        ];
        $this->assertSame([], $sut->validateInput($input, $parameters));
    }

    public function test2()
    {
        $sut = new \ParameterValidator();
        $input = ['username' => 'joe'];
        $parameters = [
            [
                'user' => [
                    'name' => 'User',
                    'type' => 'text',
                ],
            ]
        ];
        $this->assertNotEmpty($sut->validateInput($input, $parameters));
    }
}
