<?php

use LTDBeget\Network\Ip;
use LTDBeget\Network\IpUsable;
use LTDBeget\Network\IpUsableRange;
use LTDBeget\Network\Mask;
use LTDBeget\Network\Subnet;
use LTDBeget\Network\SubnetRange;
use LTDBeget\Network\tests\mock\MockSubnetImplement;

class SubnetTest extends PHPUnit_Framework_TestCase {

    public function ValidData(){
        return [
            [
                [
                    'network' => '192.168.1.0',
                    'mask_prefix' => 26,
                    'ip_usage_first' => '192.168.1.1',
                    'ip_usage_last' => '192.168.1.62',
                    'broadcast' => '192.168.1.63'
                ]
            ],
            [
                [
                    'network' => '8.8.8.0',
                    'mask_prefix' => 22,
                    'ip_usage_first' => '8.8.8.1',
                    'ip_usage_last' => '8.8.11.254',
                    'broadcast' => '8.8.11.255'
                ]
            ]
        ];
    }


    /**
     * @param array $data
     * @dataProvider validData
     */
    public function testCalculateForSubnet($data)
    {
        $ip = new Ip($data['network']);
        $mask = new Mask($data['mask_prefix']);
        $subnet = new Subnet($ip, $mask);

        $this->assertTrue($subnet->getIpUsageFirst()->getIp()->asString() == $data['ip_usage_first']);
        $this->assertTrue($subnet->getIpUsageLast()->getIp()->asString() == $data['ip_usage_last']);
        $this->assertTrue($subnet->getBroadcast()->getIp()->asString() == $data['broadcast']);
    }


//    /**
//     * Тест проверяет, что Subnet загружает иерархию из внешнего объекта и предоставляет возможность пройтись по ней
//     */
//    public function testLoadSubnet()
//    {
//        //Проверка, что мы прошлись по всем подсетям
//        $subnet_expected_list = [  # Not sorted list
//            "192.168.2.0/24",
//            "192.168.5.0/25",
//            "192.168.5.128/25",
//            "192.168.3.0/24",
//        ];
//
//        $ip = new Ip('192.168.0.0');
//        $mask = new Mask(16);
//        $subnet = new Subnet($ip, $mask);
//
//        $mock_implement = new MockSubnetImplement($subnet_expected_list);
//        $subnet->setImplementation($mock_implement);
//
//        $subnet_range = $subnet->getSubnetRange();
//        $this->assertTrue(count($subnet_range) == 4);
//
//        foreach ($subnet_range as $subnet_node) {
//            $this->assertTrue($subnet_node->isSubnet());
//            $this->assertTrue(in_array((string) $subnet_node, $subnet_expected_list));
//
//            $key = array_search((string) $subnet_node, $subnet_expected_list);
//            unset($subnet_expected_list[$key]);
//        }
//        $this->assertTrue(empty($subnet_expected_list), 'Прошлись не по всем subdomains');
//    }
//
//    /**
//     * Тест проверяет, что Subnet загружает иерархию из внешнего объекта и предоставляет возможность пройтись по ней
//     */
//    public function testLoadSubnetWithSubnets()
//    {
//        $expected_structure = [  # this is for verify the structure
//            '192.168.0.0/16' => [
//                '192.168.2.0/24' => [],
//                '192.168.3.0/24' => [],
//                '192.168.5.0/25' => [
//                    '192.168.5.0/29' => [],
//                    '192.168.5.16/29' => [],
//                ],
//                '192.168.5.128/25' => [],
//            ],
//            '8.8.0.0/16' => [],
//            '8.9.0.0/16' => [
//                '8.9.0.128/25' => []
//            ]
//        ];
//        ksort($expected_structure);
//        $expected_structure_string = print_r($expected_structure, true);
//
//        $subnet_list_from_db = [ # Our not sorted data from business logic
//            "192.168.0.0/16",
//            "192.168.2.0/24",
//            "192.168.5.0/25",
//            "192.168.5.128/25",
//            "192.168.3.0/24",
//            "192.168.5.0/29",
//            "192.168.5.16/29",
//            "8.8.0.0/16",
//            "8.9.0.0/16",
//            "8.9.0.128/25"
//        ];
//
//        $mock_implement = new MockSubnetImplement($subnet_list_from_db);
//        $ready_array = $mock_implement->getSubnetList(0, 9999999999);
//
//        $subnet_range = SubnetRange::createByArray($ready_array);
//        $result = self::getArrayStructureFromSubnetRangeRecursive($subnet_range);
//
//        ksort($result);
//        $result_string = print_r($result, true);
//        $this->assertTrue($expected_structure_string == $result_string, 'Полученные данные не сошлись с ожидаемыми');
//    }
//
//    /**
//     * Тест проверяет, что Subnet загружает иерархию из внешнего объекта и предоставляет возможность пройтись по ней
//     */
//    public function testLoadSubnetWithSubnetsAndIp()
//    {
//        $expected_structure = [
//             '192.168.0.0/16' => [
//                 '192.168.2.0/24'   => [],
//                 '192.168.3.0/24'   => [],
//                 '192.168.5.0/25'   => [
//                     '192.168.5.0/29'  => [
//                         0 => '192.168.5.2/32',
//                         1 => '192.168.5.3/32'
//                     ],
//                     '192.168.5.16/29' => [],
//                     0 => '192.168.5.230/32'
//                 ],
//                 '192.168.5.128/25' => [],
//             ],
//             '8.8.0.0/16'     => [
//                 0 => '8.8.8.8/32'
//             ],
//             '8.9.0.0/16'     => [
//                 '8.9.0.128/25' => []
//             ]
//        ];
//        ksort($expected_structure);
//        $expected_structure_string = print_r($expected_structure, true);
//
//        $subnet_list_from_db = [
//            "192.168.0.0/16",
//            "192.168.2.0/24",
//            "192.168.5.0/25",
//            "192.168.5.128/25",
//            "192.168.3.0/24",
//            "192.168.5.0/29",
//            "192.168.5.16/29",
//            "8.8.0.0/16",
//            "8.9.0.0/16",
//            "8.9.0.128/25"
//        ];
//
//        $ip_list_from_db = [
//            '192.168.5.3/32',
//            '8.8.8.8/32',
//            '192.168.5.2/32',
//            '192.168.5.230/32'
//        ];
//
//        $mock_implement = new MockSubnetImplement(array_merge($subnet_list_from_db, $ip_list_from_db));
//        $ready_array = $mock_implement->getSubnetList(0, 9999999999);
//
//        $subnet_range = SubnetRange::createByArray($ready_array);
//        $result = self::getArrayStructureFromSubnetRangeRecursive($subnet_range);
//
//        ksort($result);
//        $result_string = print_r($result, true);
//        $this->assertTrue($expected_structure_string == $result_string, 'Полученные данные не сошлись с ожидаемыми');
//    }
//
//    /**
//     * @param SubnetRange|Subnet $node
//     * @return array|bool|string
//     * @throws Exception
//     */
//    private static function getArrayStructureFromSubnetRangeRecursive($node)
//    {
//        $result = [];
//
//        if ($node instanceof SubnetRange) {
//
////                     '192.168.5.0/29'  => [
////                         0 => '192.168.5.2/32',
////                         1 => '192.168.5.3/32'
////                     ],
////                     '192.168.5.16/29' => [],
//
//            foreach ($node as $subnet_node) {
//                if ( ! ($subnet_node instanceof Subnet) ) {
//                    throw new Exception('Не ожиданный тип #2');
//                }
//
//                $result[(string) $subnet_node] = self::getArrayStructureFromSubnetRangeRecursive($subnet_node);
//            }
//
//        } elseif($node instanceof IpUsableRange) {
//
////            [
////                         0 => '192.168.5.2/32',
////                         1 => '192.168.5.3/32'
////            [
//
//            foreach ($node as $ip_node) {
//                if ( ! ($ip_node instanceof IpUsable) ) {
//                    throw new Exception('Не ожиданный тип #3');
//                }
//
//                $result[] = (string) $ip_node;
//            }
//
//        } elseif ($node instanceof Subnet) {
//
//            $subnet_range = $node->getSubnetRange();
//            $subnet_range_result = self::getArrayStructureFromSubnetRangeRecursive($subnet_range);
//
//            $ip_range = $node->getIpRange(Subnet::ONLY_LEASED);
//            $ip_range_result = self::getArrayStructureFromSubnetRangeRecursive($ip_range);
//
//            $result[(string) $node] = array_merge($subnet_range_result, $ip_range_result);
////                 '192.168.5.0/25'   => [
////                     '192.168.5.0/29'  => [
////                         0 => '192.168.5.2/32',
////                         1 => '192.168.5.3/32'
////                     ],
////                     '192.168.5.16/29' => [],
////                     0 => '192.168.5.230/32'
////                 ],
//
//        }else {
//            throw new Exception('Не ожиданный тип');
//        }
//
//        return $result;
//    }

//    public function testLoadLeasedIP()
//    {
//        $ip = new Ip('192.168.0.0');
//        $mask = new Mask(16);
//
//        //192.168.2.0/24 -> [Leased ip: 192.168.2.3, 192.168.2.4, 192.168.2.69]
//        $occupied_ip_expected_list = [
//            '192.168.2.3/32', '192.168.2.4/32', '192.168.2.69/32'
//        ];
//
//        $subnet = new Subnet($ip, $mask);
//
//        $ipUsableRange = $subnet->getIpRange();
//        $leased_ip_list = $ipUsableRange->getLeasedIPList();
//
//        $this->assertTrue(count($leased_ip_list) == 3);
//        foreach($leased_ip_list as $leased_ip)
//        {
//            $this->assertTrue($leased_ip->isUsableIp());
//            $this->assertTrue(in_array((string) $leased_ip, $occupied_ip_expected_list));
//
//            $key = array_search((string) $leased_ip, $occupied_ip_expected_list);
//            unset($occupied_ip_expected_list[$key]);
//        }
//    }
}

