<?php

namespace JWage\APNS\Tests;

use JWage\APNS\Payload;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    private $payload;

    public function testGetPayload()
    {
        $expectedPayload = array(
            'aps' => array(
                'alert' => array(
                    'title' => 'title',
                    'body' => 'body',
                ),
                'url-args' => array(
                    'deep link'
                ),
            ),
        );
        $payload = $this->payload->getPayload();
        $this->assertEquals($expectedPayload, $payload);
    }

    protected function setUp()
    {
        $this->payload = new Payload('title', 'body', 'deep link');
    }
}
