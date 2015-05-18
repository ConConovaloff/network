<?php

namespace LTDBeget\Network;


/**
 * "Конечный" Ip для использования. (имеет маску 32)
 */
class IpUsable extends Network
{
    public function isIpUsable()
    {
        return true;
    }

    public function getType()
    {
        return Network::TYPE_IP_USABLE;
    }
}
