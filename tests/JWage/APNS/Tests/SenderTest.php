<?php

namespace JWage\APNS\Tests;

use JWage\APNS\Payload;
use JWage\APNS\Sender;
use PHPUnit\Framework\TestCase;

;

class SenderTest extends TestCase
{
    private $client;

    private $sender;

    public function testSend()
    {
        $payload = new Payload('title', 'body', 'deep link');
        $this->client->expects($this->once())
            ->method('sendPayload')
            ->with('device token', $payload);
        $this->sender->send('device token', 'title', 'body', 'deep link');
    }

    protected function setUp()
    {
        $this->client = $this->getMockBuilder('JWage\APNS\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sender = new Sender($this->client);
    }
}
