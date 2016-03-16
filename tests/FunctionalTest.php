<?php

use Clue\React\Soap\Factory;
use Clue\React\Soap\Client;
use Clue\React\Soap\Proxy;
use Clue\React\Block;

class FunctionalTest extends TestCase
{
    /**
     * @var React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
        $factory = new Factory($this->loop);

        $promise = $factory->createClient('http://www.thomas-bayer.com/axis2/services/BLZService?wsdl');

        $this->expectPromiseResolve($promise);
        $this->client = Block\await($promise, $this->loop);
        /* @var $client Client */
    }

    public function testBlzService()
    {
        $this->assertCount(2, $this->client->getFunctions());
        $this->assertCount(3, $this->client->getTypes());

        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectPromiseResolve($promise);
        $result = Block\await($promise, $this->loop);

        $this->assertInternalType('object', $result);
    }

    public function testBlzServiceWithInvalidBlz()
    {
        $api = new Proxy($this->client);

        $promise = $api->getBank(array('blz' => 'invalid'));

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        Block\await($promise, $this->loop);
    }

    public function testBlzServiceWithInvalidMethod()
    {
        $api = new Proxy($this->client);

        $promise = $api->doesNotexist();

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        Block\await($promise, $this->loop);
    }

    public function testGetLocationForFunctionName()
    {
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation('getBank'));
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation('getBank'));
    }

    public function testGetLocationForFunctionNumber()
    {
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation(0));
    }

    /**
     * @expectedException SoapFault
     */
    public function testGetLocationOfUnknownFunctionNameFails()
    {
        $this->client->getLocation('unknown');
    }

    /**
     * @expectedException SoapFault
     */
    public function testGetLocationForUnknownFunctionNumberFails()
    {
        $this->assertEquals('http://www.thomas-bayer.com/axis2/services/BLZService', $this->client->getLocation(100));
    }

    public function testWrongLocationOverride()
    {
        $api = new Proxy($this->client->withTarget('nonsense.not.existing'));

        $promise = $api->getBank(array('blz' => '12070000'));

        $this->expectPromiseReject($promise);

        $this->setExpectedException('Exception');
        Block\await($promise, $this->loop);
    }

    public function testCorrectLocationOverride()
    {
        $this->client->withTarget('nonsense.not.existing');
        $this->client->withTarget('http://www.thomas-bayer.com/axis2/services/BLZService');
        $this->testBlzService();
    }
}
