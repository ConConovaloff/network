<?php

use LTDBeget\Network\Ip;

class IpTest extends PHPUnit_Framework_TestCase {
    /**
     * @var int 192.168.2.1
     */
    public $ip_int = 3232236033;
    public $ip_string = '192.168.2.1';


    public function testGetByInt()
    {
        $ip = new Ip($this->ip_string);
        $this->assertTrue($ip->asInt() == $this->ip_int);
    }

    public function testGetByString()
    {
        $ip = new Ip($this->ip_int, Ip::TYPE_INT);
        $this->assertTrue($ip->asString() == $this->ip_string);
    }

    public function invalidIpInt()
    {
        return [
            [3.14],
            [Ip::MAX_INT + 1],
            [Ip::MIN_INT - 1],
        ];
    }


    /**
     * @param int $ip_int
     * @dataProvider invalidIpInt
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidIp($ip_int)
    {
        $ip = new Ip($ip_int, IP::TYPE_INT);
    }


    public function invalidIpString()
    {
        return [
            ['192.168.2.1.1'],
            ['192.168.2.256'],
            ['SomeString'],
        ];
    }

    /**
     * @param string $ip_string
     * @dataProvider invalidIpString
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidString($ip_string)
    {
        $ip = new Ip($ip_string);
    }
}
