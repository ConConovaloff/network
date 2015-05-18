<?php

namespace LTDBeget\Network;

/**
 * Абстрактная моделька для реализаций используемого ip, маски, бродкаста и других сетевых объектов которые имеют адрес.
 * Содержит в себе ip адрес и маску
 */
abstract class Network
{
    /**
     * Mask for XOR to value, for cut to 32 bit
     * in binary: 1111111111111111111111111111111100000000000000000000000000000000
     * todo: вынести это кудато получше
     */
    const MASK_62_TO_32 = 18446744069414584000;

    /**
     * 11111111111111111111111111111111
     */
    const MASK_32 = 4294967295;

    const TYPE_SUBNET = 'subnet';
    const TYPE_IP_USABLE = 'ip_usable';
    const TYPE_BROADCAST = 'broadcast';

    /**
     * @var Ip
     */
    protected $ip;

    /**
     * @var Mask
     */
    protected $mask;


    /**
     * @param Ip   $ip
     * @param Mask $mask
     */
    public function __construct(Ip $ip, Mask $mask = null)
    {
        $this->setIp($ip);
        $this->setMask($mask ?: new Mask(32));
    }

    abstract public function getType();

    /**
     * @param Ip $ip
     */
    private function setIp(Ip $ip)
    {
        $this->ip = $ip;
    }


    /**
     * @param Mask $mask
     */
    private function setMask(Mask $mask)
    {
        $this->mask = $mask;
    }

    public function getIp()
    {
        return $this->ip;
    }


    public function getMask()
    {
        return $this->mask;
    }


    public function isSubnet()
    {
        return false;
    }


    public function isBroadcast()
    {
        return false;
    }


    public function isIpUsable()
    {
        return false;
    }

    /**
     * @return string Out: 192.168.2.0/24
     */
    public function toString()
    {
        return $this->getIp()->asString() . '/' . $this->getMask()->asPrefix();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }


    /**
     * @param string $networkString In: 192.168.2.0/24
     *                              Or: 192.168.2.1
     * @return IpUsable|Subnet
     */
    public static function createByString($networkString)
    {
        if (strpos($networkString, '/')) {
            $network_array = explode("/", $networkString);
            $ip   = new Ip($network_array[0]);
            $mask = new Mask($network_array[1]);

            return new Subnet($ip, $mask);

        } else {
            $ip   = new Ip($networkString);
            $mask = new Mask(32);

            return new IpUsable($ip, $mask);
        }
    }

    public function getNetwork()
    {
        return null;
    }

    public function getBroadcast()
    {
        return null;
    }

    public function getIpRange()
    {
        return null;
    }

    public function getIpUsageFirst()
    {
        return null;
    }

    public function getIpUsageLast()
    {
        return null;
    }

    public function getSubnetRange()
    {
        return null;
    }
}
