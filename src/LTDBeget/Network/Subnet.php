<?php

namespace LTDBeget\Network;
use LTDBeget\Network\implement\BusinessLogic;


/**
 * @nil == not in library
 *
 * Толстая модель подсети.
 *   - содержит массив используемых ip шников
 *      первый\последний используемый ip шник
 *   - свой адрес
 *   - broadcast адрес
 *
 * todo: это
 * 192.168.2.5/30 - является не валидным адресом подсети
 * 192.168.2.4/30 - является валидным адресом подсети
 *
 * todo: range имеете long ip-шника, маски -> увеличивает их на один и возвращает Ip
 * todo: Проверять, что это subnet (192.168.2.0/24)
 */
class Subnet extends Network implements \Iterator
{
    /**
     * Mask for XOR to value, for cut to 32 bit
     * in binary: 1111111111111111111111111111111100000000000000000000000000000000
     */
    const MASK_62_TO_32 = 18446744069414584000;

    /**
     * constant for range type
     */
    const ONLY_LEASED = 'only_leased';

    /**
     * @var IP
     */
    private $network;

    /**
     * @var Broadcast
     */
    private $broadcast;

    /**
     * @var IpUsable
     */
    private $ip_usable_first;

    /**
     * @var IpUsable
     */
    private $ip_usable_last;

    /**
     * @var IpUsableRange
     */
    private $ip_usable_range;

    /**
     * @var SubnetRange на случай когда наша подсеть разделена еще на другие сети
     */
    private $subnet_range;

    /**
     * Представляет реализацию доступа к данным
     * @var BusinessLogic
     */
    private $implement;


    /**
     * @param Ip   $ip   In: Ip('192.168.2.0')
     * @param Mask $mask In: Mask(24)
     * @param array $children_tree древовидный массив сетей нижнего уровня (массив, так как в целях производительности
     *                             не следует создавать объект пока не обратились)
     */
    public function __construct(Ip $ip, Mask $mask, $children_tree = null)
    {
        parent::__construct($ip, $mask);
        $this->children_tree = $children_tree;
    }


    /**
     * @param $subnet_tree
     * @return Subnet
     */
    public static function createByTree($subnet_tree)
    {
        return new Subnet();
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
     * todo: а нужно ли нам получение первого и последнего?
     * todo: изначально, мы планируем использовать ipRange для предоставление ip - шников.
     *
     * @return IpUsable
     * @throws \Exception
     */
    public function getIpUsageFirst()
    {
        if ($this->ip_usable_first instanceof IpUsable) {
            return $this->ip_usable_first;

        } elseif ($this->ip_usable_range instanceof IpUsableRange) {
            $this->ip_usable_first = $this->ip_usable_range->getFirstIp();

        } elseif ($this->ip instanceof Ip && $this->mask instanceof Mask) {
            $ip_int = self::calculateFirstIp($this->ip->asInt(), $this->mask->asInt()) + 1;
            $ip = new Ip($ip_int, Ip::TYPE_INT);
            $this->ip_usable_first = new IpUsable($ip);

        } else {
            throw new \Exception('Subnet object has no ip address or mask');
        }

        return $this->ip_usable_first;
    }


    public function getIpUsageLast()
    {
        if ($this->ip_usable_last instanceof IpUsable) {
            return $this->ip_usable_last;

        } elseif ($this->ip_usable_range instanceof IpUsableRange) {
            $this->ip_usable_last = $this->ip_usable_range->getLastIp();

        } elseif ($this->ip instanceof Ip && $this->mask instanceof Mask) {
            $ip_int = self::calculateLastIp($this->ip->asInt(), $this->mask->asInt()) - 1;
            $ip = new Ip($ip_int, Ip::TYPE_INT);
            $this->ip_usable_last = new IpUsable($ip);

        } else {
            throw new \Exception('Subnet object has no ip address or mask');
        }

        return $this->ip_usable_last;
    }


    /**
     * Проверяет указанные значения на корректность указания подсети и ip адреса
     *
     */
    public static function isValid()
    {
    }


    /**
     * Проверяет входит ли переданная подсеть|сеть в диапазон текущей
     *
     */
    public function isIncluded(Network $network)
    {
    }


    /**
     * Проверяет является ли указанная подсеть родителем для текущей
     *
     */
    public function isParent(Subnet $subnet)
    {
    }


    public function isSubnet()
    {
        return true;
    }


    /**
     * Правильно ли указан адресс подсети
     */
    public function isMissedSubnetIp()
    {

    }

//    todo: почему просто через маску не подсчитать?
//    /**
//     * Возвращает длинну подсети (кол-во адресов)
//     * @return int
//     * @throws \Exception
//     */
//    public function count()
//    {
//        if (! $this->isSubnet()) {
//            throw new \Exception('Is not subnet');
//        }
//
//        $this->ip_subnet_end_long = $this->getLastIpLong();
//        $this->ip_subnet_start_long = $this->getFirstIpLong();
//
//        return ($this->ip_subnet_end_long - $this->ip_subnet_start_long) + 1; #todo: почему +1 ?
//    }


    /**
     * Возвращает количество свободных ip адресов в данной подсети
     *
     * @nil
     *
     * @return integer
     */
    public function countFreeIp()
    {
    }


    /**
     * Возвращает итератор ip-шников для использования
     * @param string $type
     * @return IpUsableRange
     */
    public function getIpRange($type = 'default_type')
    {
        if ($this->ip_usable_range[$type] instanceof IpUsableRange) {
            return $this->ip_usable_range[$type];
        }

        $this->ip_usable_range[$type] = $this->calculateIpRange($type);

        return $this->ip_usable_range[$type];
    }


    /**
     * Вернуть итератор по действующим ip адресам
     * Notice: это меняется в зависимости от бизнес логики. По умолчанию, все ip-шники подсети являются используемые
     *         а других подсетей в ней нету.
     */
    private function calculateIpRange($type)
    {
        if ($this->implement instanceof BusinessLogic) {
            /**
             * [
             *   0 => ['ip' => '111111111', 'random_field' => 'my_random_data_for_ip'],
             *   1 => ['ip' => '111111133', 'random_field' => 'my_random_data_for_ip2'],
             *   2 => ['ip' => '222222222', 'random_field' => 'my_random_data_for_ip3'],
             * ]
             * @var $ip_list - массив ip адрессов
             */
            $ip_list = $this->implement->getIpList($type);
            return new IpUsableRange($ip_list);
        }

        $ip_first = $this->getIpUsageFirst();
        $ip_last = $this->getIpUsageLast();
        return new IpUsableRange([$ip_first->getIp()->asInt(), $ip_last->getIp()->asInt()]);
    }


    /**
     * Вернуть итератор по subnet-ам внутри этой subnet
     * Notice: это меняется в зависимости от бизнес логики. По умолчанию, у нас тут нет других subnet.
     *
     * @return SubnetRange
     * @throws exception\ApplicationException
     */
    private function calculateSubnetRange()
    {
        if ($this->children_tree) {
            //todo: унас тут дублирование
            $subnet_range = [];
            foreach ($this->children_tree as $subnet_lief) {
                $ip = new Ip($subnet_lief['ip'], IP::TYPE_INT);
                $mask = new Mask($subnet_lief['mask']);
                $subnet_range[] = new Subnet($ip, $mask, $subnet_lief['childs']);
            }

            return new SubnetRange($subnet_range);
        }

        // Если реализация не добавлена, то информации о подсетях нету.
        if (!$this->implement) {
            return new SubnetRange();
        }

        $ip = $this->getIp()->asInt();
        $mask = $this->getMask()->asInt();

        $ip_first = $this->calculateFirstIp($ip, $mask);
        $ip_last = $this->calculateLastIp($ip, $mask);

        /**
         * [
         *      ['ip' => 1533724672, 'mask' => 21],
         *      ['ip' => 3232236032, 'mask' => 24]
         * ]
         */
        $implement_subnet_target_list = $this->implement->getSubnetTargetList($ip_first, $ip_last);
        $subnet_tree = self::calculateTreeSubnet($implement_subnet_target_list);

        $subnet_range = [];
        foreach ($subnet_tree as $subnet_lief) {
            $ip = new Ip($subnet_lief['ip'], IP::TYPE_INT);
            $mask = new Mask($subnet_lief['mask']);
            $subnet_range[] = new Subnet($ip, $mask, $subnet_lief['childs']);
        }

        return new SubnetRange($subnet_range);
    }


    /**
     * @return SubnetRange
     */
    public function getSubnetRange()
    {
        if ($this->subnet_range instanceof SubnetRange) {
            return $this->subnet_range;
        }

        $this->subnet_range = $this->calculateSubnetRange();

        return $this->subnet_range;
    }


    /**
     * @return Broadcast
     */
    public function getBroadcast()
    {
        if ($this->broadcast instanceof Broadcast) {
            return $this->broadcast;
        }

        $ip_int = $this->getIp()->asInt();
        $mask = $this->getMask();

        $ip_last = self::calculateLastIp($ip_int, $mask->asInt());

        $ip_last_obj = new Ip($ip_last, Ip::TYPE_INT);
        $this->broadcast = new Broadcast($ip_last_obj, $this->getMask());

//        todo: наверное нужно по всему проекту сделать примерно так же
//        $this->broadcast = Broadcast::createByInt($ip_last);

        return $this->broadcast;
    }


    /**
     * Возвращает адрес этой подсети
     * todo: подумать над более уместном название. Этот класс уже является Subnet, но здесь мы возвращаем его адрес.
     * todo: и должно ли оно возвращать Ip или под него выделить еще класс?
     */
    public function getNetwork()
    {
        if ($this->network instanceof Ip) {
            return $this->network;
        }

        $ip_int = $this->getIp()->asInt();
        $ip_mask = $this->getMask()->asInt();

        $ip_int_first = self::calculateFirstIp($ip_int, $ip_mask);
        $this->network = new Ip($ip_int_first, Ip::TYPE_INT);

        return $this->network;
    }


    /**
     * Получение первого ip адреса подсети (адрес самой подсети)
     *
     * @param int $ip_long In: 3232236293 ('192.168.3.5' in string)
     * @param int $mask_long In: 4294967040 ('255.255.254.0' in string)
     *
     * @return int Out: 3232236032 ('192.168.2.0' in string)
     */
    public static function calculateFirstIp($ip_long, $mask_long)
    {
        return $ip_long & $mask_long;
    }


    /**
     * Получение последнего ip адреса подсети (адрес broadcast)
     *
     * @param int $ip_int In: 3232236293 ('192.168.3.5' in string and 11000000101010000000001100000101 in binary)
     * @param int $mask_int In: 4294966784 ('255.255.254.0' in string and 11111111111111111111111000000000 in binary)
     *
     * @return int Out: 3232236032 ('192.168.3.255' in string)
     */
    public static function calculateLastIp($ip_int, $mask_int)
    {
        $mask_not = (~$mask_int) ^ self::MASK_62_TO_32;

        $ip_last = (($ip_int & $mask_int) + $mask_not);
        # 11000000101010000000001100000101 & 11111111111111111111111000000000 = 11000000101010000000001000000000
        # 11000000101010000000001000000000 + 00000000000000000000000111111111 = 11000000101010000000001111111111

        return $ip_last;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        // TODO: Implement current() method.
    }


    /**
     * todo: сначала проходимся по ip, [broadcast] потом по subnet
     *
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        // TODO: Implement next() method.
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        // TODO: Implement key() method.
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        // TODO: Implement valid() method.
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }


    public function getType()
    {
        return Network::TYPE_SUBNET;
    }


    public function setImplementation($implement)
    {
        $this->implement = $implement;
    }
}
