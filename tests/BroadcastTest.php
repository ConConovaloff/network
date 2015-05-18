<?php
use LTDBeget\Network\Broadcast;
use LTDBeget\Network\Ip;
use LTDBeget\Network\Mask;
use LTDBeget\Network\Network;


class BroadcastTest extends PHPUnit_Framework_TestCase
{
    public function validBroadcast()
    {
        return [
            ['192.168.2.255', 24],
            ['192.168.2.63', 26]
        ];
    }

    /**
     * @param string $ip_string
     * @param int $mask_prefix
     * @dataProvider validBroadcast
     */
    public function testType($ip_string, $mask_prefix)
    {
        $ip = new Ip($ip_string);
        $mask = new Mask($mask_prefix);
        $broadcast = new Broadcast($ip, $mask);
        $this->assertTrue($broadcast->isBroadcast());
        $this->assertTrue($broadcast->getType() == Network::TYPE_BROADCAST);
    }

    public function invalidBroadcast()
    {
        return [
            ['192.168.2.1', 24],
            ['192.168.2.255', 32]
        ];
    }

    /**
     * @param string $ip_string
     * @param int $mask_prefix
     * @dataProvider invalidBroadcast
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidBroadcast($ip_string, $mask_prefix)
    {
        $ip = new Ip($ip_string);
        $mask = new Mask($mask_prefix);
        $broadcast = new Broadcast($ip, $mask);
    }
}
