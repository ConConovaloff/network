<?php
/**
 * todo: наверное, он должен наследоваться от IpUsable
 */

namespace LTDBeget\Network;


use LTDBeget\Network\exception\IpNotValidException;

class Broadcast extends Network
{
    public function __construct(Ip $ip, Mask $mask)
    {
        parent::__construct($ip, $mask);

        if (in_array($mask->asPrefix(), [32, 31])) {
            throw new IpNotValidException('in 32 and 31 mask don`t allow use broadcast');
        }

        if (Subnet::calculateLastIp($ip->asInt(), $mask->asInt()) != $ip->asInt()) {
            throw new IpNotValidException('Is not broadcast address' . (string) $this);
        }
    }

    public function isBroadcast()
    {
        return true;
    }

    public function asInt()
    {
        return $this->getIp()->asInt();
    }

    public function getType()
    {
        return Network::TYPE_BROADCAST;
    }
}
