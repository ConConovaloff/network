<?php

namespace LTDBeget\Network\implement;

interface BusinessLogic {

    /**
     * @param array $parameters
     * @return array
     */
    function getSubnetList($parameters);

    /**
     * @param array $parameters
     * @return array
     */
    function getIpList($parameters);
}
