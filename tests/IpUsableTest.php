<?php
use LTDBeget\Network\Ip;
use LTDBeget\Network\IpUsable;

class IpUsableTest extends PHPUnit_Framework_TestCase {

    public function testToString()
    {
        $ip = new Ip('192.168.2.1');
        $ip_usable = new IpUsable($ip);

        $this->assertTrue($ip_usable->toString() == '192.168.2.1/32');
        $this->assertTrue($ip_usable == '192.168.2.1/32');
    }
}
