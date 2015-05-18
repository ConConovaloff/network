<?php

namespace LTDBeget\Network\tests\mock;


use LTDBeget\Network\implement\BusinessLogic;

class MockSubnetImplement implements BusinessLogic
{
    private $network_list;

    /**
     * @param $network_list
     * [
     *   '192.168.2.0/24',
     *   '192.168.3.0/24',
     * ]
     */
    public function __construct($network_list)
    {
        foreach ($network_list as $network) {
            list($ip_string, $mask_string) = explode('/', $network);
            $this->network_list[] = [
                'ip' => ip2long($ip_string),
                'mask' => (int) $mask_string,
                'ip_str' => $ip_string,
            ];
        }
    }


    /**
     * @param int $first
     * @param int $last
     * @return array
     */
    function getSubnetList($first, $last) {

        $result = [];

        foreach ($this->network_list as $network) {
            if ($first <= $network['ip'] && $network['ip'] <= $last) {
                $result[] = $network;
            }
        }

        return $result;
    }


    /**
     * @param array $parameters
     * @return array
     */
    function getIpList($parameters)
    {
        // TODO: Implement getIpList() method.
    }
}
