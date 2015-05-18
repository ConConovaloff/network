<?php

namespace LTDBeget\Network;


/**
 * Итератор по Subnet
 *
 * Пример:
 *   Дано:
 *     - 192.168.2.0/23 (192.168.2.1 - 192.168.3.254)
 *     - В этой сети создана подсеть 192.168.2.4/30 (192.168.2.4 - 192.168.2.7)
 *                                 и 192.168.2.40/29 (192.168.2.40 - 192.168.2.47)
 *
 *   В итоге, мы должны пройтись по этим двум подсетям
 */
class SubnetRange implements \Iterator, \Countable
{
    /**
     * @var Subnet[]
     */
    private $subnet_list;


    /**
     * @param Subnet[] $subnet_list
     * @throws \Exception если передали не Subnet
     */
    public function __construct($subnet_list = [])
    {
        foreach ($subnet_list as $subnet) {
            if ( ! ($subnet instanceof Subnet) ) {
                throw new \Exception('SubnetRange may create only by Subnet');
            }
        }

        $this->subnet_list = $subnet_list;
        $this->position = 0;
    }


    public static function createByArray($ready_array)
    {
        $subnet_tree = self::calculateTreeSubnet($ready_array);

        $subnet_range = [];
        foreach ($subnet_tree as $subnet_lief) {
            if ($subnet_lief['mask'] == 32) {
                continue;
            }

            $mask = new Mask($subnet_lief['mask']);
            $ip = new Ip($subnet_lief['ip'], IP::TYPE_INT);
            $subnet_range[] = new Subnet($ip, $mask, $subnet_lief['childs']);
        }

        return new self($subnet_range);
    }

    /**
     * @param $implement_subnet_target_list
     * [
     *      ['ip' => 1533724672, 'mask' => 21],
     *      ['ip' => 3232236032, 'mask' => 24]
     * ]
     *
     * @return array
     **/
    private static function calculateTreeSubnet($implement_subnet_target_list)
    {
        usort($implement_subnet_target_list, function($a, $b){
            $compare = $a['ip'] - $b['ip'];

            if ($compare === 0) {
                $compare = $a['mask'] - $b['mask'];
            }

            return $compare;
        });

        $subnet_with_parent = self::setParents($implement_subnet_target_list);

        foreach ($subnet_with_parent as $key => $node) {
            $subnet_with_parent[$key]['id'] = $key;
        }

        return self::treeFromList($subnet_with_parent);
    }

    public static function treeFromList($network_list, $parent_id=null)
    {
        $result = [];

        foreach ($network_list as $key => $network) {
            if ($parent_id === $network['parent_id']) {
                unset($network_list[$key]);
                $network['childs'] = self::treeFromList($network_list, $network['id']);
                $result[] = $network;
            }
        }

        return $result;
    }

    public static function IPNetContains ($netA, $netB)
    {
        return (-2 == self::IPNetworkCmp ($netA, $netB));
    }

    public static function IPNetworkCmp ($netA, $netB)
    {
        $netA['ip_bin'] = $netA['ip'];
        $netB['ip_bin'] = $netB['ip'];

        $ret = self::IPCmp ($netA['ip_bin'], $netB['ip_bin']);
        if ($ret == 0) {
            $ret = $netA['mask_int'] < $netB['mask_int'] ? -1 : ($netA['mask_int'] > $netB['mask_int'] ? 1 : 0);
        }

        if ($ret == -1 and $netA['ip_bin'] === ($netB['ip_bin'] & $netA['mask_int'])) {
            $ret = -2;
        }

        if ($ret == 1 and $netB['ip_bin'] === ($netA['ip_bin'] & $netB['mask_int'])) {
            $ret = 2;
        }

        return $ret;
    }

    public static function IPCmp ($ip_binA, $ip_binB)
    {
        return self::numSign ($ip_binA - $ip_binB);
    }

    public static function numSign ($x)
    {
        if ($x < 0)
            return -1;
        if ($x > 0)
            return 1;
        return 0;
    }

    /**
     * @param $implement_subnet_target_list
     * @return mixed
     */
    private static function setParents($implement_subnet_target_list)
    {
        $stack = array();
        foreach ($implement_subnet_target_list as $net_id => &$net) {
            $net['mask_int'] = Mask::prefixToInt($net['mask']);

            while (!empty ($stack)) {
                $top_id = $stack[count($stack) - 1];
                if (!self::IPNetContains($implement_subnet_target_list[$top_id], $net)) {
                    array_pop($stack);
                } else {
                    $net['parent_id'] = $top_id;
                    break;
                }
            }

            if (empty($stack)) {
                $net['parent_id'] = NULL;
            }

            array_push($stack, $net_id);
        }
        unset ($stack);

        return $implement_subnet_target_list;
    }

    /**
     * @return Subnet
     */
    public function current()
    {
        return $this->subnet_list[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->subnet_list[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function count()
    {
        return count($this->subnet_list);
    }
}
