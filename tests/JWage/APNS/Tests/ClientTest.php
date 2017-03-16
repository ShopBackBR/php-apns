<?php

namespace JWage\APNS\Tests;

use JWage\APNS\ApnsMessage;
use JWage\APNS\Client;
use JWage\APNS\Payload;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $socketClient;

    public function testSendPayload()
    {
        $apnsMessage = $this->getMockBuilder('JWage\APNS\ApnsMessage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client->setApnsMessage($apnsMessage);

        $apnsMessage->expects($this->once())
            ->method('getBinaryMessage')
            ->will($this->returnValue('test binary message'));

        $this->socketClient->expects($this->once())
            ->method('write')
            ->with('test binary message')
            ->will($this->returnValue('success'));

        $payload = new Payload('title', 'body', 'deep link');
        $this->client->sendPayload('97213C2CA2146AF258B098611394FD6943FA730FF65E6797A85D3A0DC713A84C', $payload);
    }

    protected function setUp()
    {
        $this->socketClient = $this->getMockBuilder('JWage\APNS\SocketClient')
            ->disableOriginalConstructor()
            ->getMock();
        $this->client = new ClientStub($this->socketClient);
    }
}

class ClientStub extends Client
{
    private $apnsMessage;

    public function setApnsMessage(ApnsMessage $apnsMessage)
    {
        $this->apnsMessage = $apnsMessage;
    }

    protected function createApnMessage($deviceToken, Payload $payload)
    {
        return $this->apnsMessage;
    }
}
