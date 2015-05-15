<?php

use BegetNetwork\Ip;

class IpTest extends \PHPUnit_Framework_TestCase {
    public function validIpString()
    {
        return array(
            array('192.168.2.1'),
        );
    }

    public function invalidIpString()
    {
        return array(
            array('192.168.2.1.56'),
        );
    }


    /**
     * @param string $ip
     *
     * @dataProvider validIpString
     */
    public function testValidIp($ip)
    {
        $ip_obj = new Ip($ip);
        $this->assertTrue($ip_obj->getIpAsString() == $ip);
    }

    /**
     * @param string $ip
     *
     * @dataProvider invalidIpString
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Bad Ip address
     */
    public function testInvalidIp($ip)
    {
        $ip_obj = new Ip($ip);
        $this->assertTrue($ip_obj->getIpAsString() == $ip);
    }
}
