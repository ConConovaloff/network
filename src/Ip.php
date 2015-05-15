<?php
/**
 * Test
 */

namespace BegetNetwork;


class Ip
{
    /**
     * @var string
     */
    private $ip_string;

    public function __construct($ip)
    {
        if (count(explode('.', $ip)) != 4) {
            throw new \InvalidArgumentException('Bad Ip address');
        }

        $this->ip_string = $ip;
    }

    /**
     * @return string
     */
    public function getIpAsString()
    {
        return $this->ip_string;
    }
}
