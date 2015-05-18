<?php

use LTDBeget\Network\IpUsable;
use LTDBeget\Network\Network;

class NetworkTest extends \PHPUnit_Framework_TestCase
{
    public function testParseIpString()
    {
        $network = Network::createByString('192.168.2.1');
        $this->assertTrue($network->getIp()->asInt() == ip2long('192.168.2.1'));
        $this->assertTrue($network->getMask()->asPrefix() == 32);

        $this->assertTrue(!$network->isSubnet());
    }

    public function testParseIpCidr()
    {
        $network = Network::createByString('192.168.2.1/24');
        $this->assertTrue($network->getIp()->asInt() == ip2long('192.168.2.1'));
        $this->assertTrue($network->getMask()->asPrefix() == 24);
        $this->assertTrue($network->getNetwork()->asInt() == ip2long('192.168.2.0'));
        $this->assertTrue($network->getBroadcast()->asInt() == ip2long('192.168.2.255'));
    }

    public function testParseIpCidr2()
    {
        $network = Network::createByString('192.168.2.1/24');
        $this->assertTrue($network->getIp()->asInt() == ip2long('192.168.2.1'));
        $this->assertTrue($network->getMask()->asPrefix() == 24);
        $this->assertTrue($network->getNetwork()->asInt() == ip2long('192.168.2.0'));
        $this->assertTrue($network->getBroadcast()->asInt() == ip2long('192.168.2.255'));
    }

    public function testIfNotSubnetReturnNull()
    {
        //todo: а надо ли нам оно? С одной стороны, мы создаем объект динамически и не знаем что можем там получить.
        //todo: поэтому должны всегда проверять тип полученного объекта, а если не проверили, то получать выброс из
        //todo: программы. С другой стороны, когда мы просим вернуть broadcast из того, что его не имеет, то логично
        //todo: вернуть null.

        $network = Network::createByString('192.168.2.1');
        $this->assertTrue($network->getNetwork() == null);
        $this->assertTrue($network->getBroadcast() == null);
        $this->assertTrue($network->getIpRange() == null);
        $this->assertTrue($network->getIpUsageFirst() == null);
        $this->assertTrue($network->getIpUsageLast() == null);
        $this->assertTrue($network->getSubnetRange() == null);
    }

    public function testTypeIpUsable()
    {
        $network = Network::createByString('192.168.2.1');
        $this->assertTrue($network->isIpUsable());
        $this->assertTrue($network->getType() == Network::TYPE_IP_USABLE);
        $this->assertTrue($network instanceof IpUsable);

        $this->assertFalse($network->isBroadcast());
        $this->assertFalse($network->isSubnet());
    }
}
