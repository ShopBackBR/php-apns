<?php

namespace JWage\APNS\Tests;

use PHPUnit\Framework\TestCase;
use JWage\APNS\Payload;


class PayloadTest extends TestCase
{
    private $payload;

    protected function setUp()
    {
        $this->payload = new Payload('title', 'body', 'deep link');
    }

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
}
