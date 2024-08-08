<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Configuration;
use PHPUnit\Framework\TestCase;
use Request;
use UrlEncryptionService;

final class UrlEncryptionTest extends TestCase
{
    public function testGetKey(): void
    {
        $this->assertEquals(null, UrlEncryptionService::getKey());

        Configuration::loadConfiguration([
            'system' => [
                'enc_url_key' => '1234567890123456789012345678901234567890'
            ]
        ]);
        $this->assertEquals('1234567890123456789012345678901234567890', UrlEncryptionService::getKey());
    }

    public function testEnabled(): void
    {
        Configuration::loadConfiguration([
            'system' => [
                'enc_url_key' => 'aBigStupidDummyKeyForEncryptingURLs'
            ]
        ]);
        $this->assertTrue(UrlEncryptionService::enabled());

        Configuration::loadConfiguration([
            'system' => [
                'enc_url_key' => ''
            ]
        ]);
        $this->assertTrue(!UrlEncryptionService::enabled());
    }

    // NOTE: Testing encryption on its own with a 'known' key isn't really
    //        feasible because the initialization vector changes with each call.
    public function testEncryptionAndDecryption(): void
    {
        Configuration::loadConfiguration([
            'system' => [
                'enc_url_key' => 'aBigStupidDummyKeyForEncryptingURLs'
            ]
        ]);

        $q = 'action=display&bridge=TheHackerNewsBridge&format=Html';

        $params = [];
        parse_str($q, $params);

        $enc = UrlEncryptionService::generateFromQueryString($q);
        $req = Request::fromCli([
            UrlEncryptionService::PARAMETER_NAME => $enc
        ]);

        $req->tryDecryptUrl();

        $this->assertEquals('display', $req->get('action'));
        $this->assertEquals('TheHackerNewsBridge', $req->get('bridge'));
        $this->assertEquals('Html', $req->get('format'));
    }
}