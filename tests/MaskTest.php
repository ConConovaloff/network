<?php

use LTDBeget\Network\Mask;

class MaskTest extends PHPUnit_Framework_TestCase
{
    /**
     * 255.255.255.0
     */
    public $mask_source_int = 4294967040;
    public $mask_source_string = '255.255.255.0';
    public $mask_source_prefix = 24;


    public function testGetAsInt()
    {
        $mask = new Mask($this->mask_source_prefix, Mask::TYPE_PREFIX);
        $this->assertTrue($mask->asInt() == $this->mask_source_int);

        $mask = new Mask($this->mask_source_string, Mask::TYPE_STRING);
        $this->assertTrue($mask->asInt() == $this->mask_source_int);
    }

    public function testGetAsString()
    {
        $mask = new Mask($this->mask_source_int, Mask::TYPE_INT);
        $this->assertTrue($mask->asString() == $this->mask_source_string);

        $mask = new Mask($this->mask_source_prefix, Mask::TYPE_PREFIX);
        $this->assertTrue($mask->asString() == $this->mask_source_string);

    }

    public function testGetAsPrefix()
    {
        $mask = new Mask($this->mask_source_string, Mask::TYPE_STRING);
        $this->assertTrue($mask->asPrefix() == $this->mask_source_prefix);

        $mask = new Mask($this->mask_source_int, Mask::TYPE_INT);
        $this->assertTrue($mask->asPrefix() == $this->mask_source_prefix);
    }

    public function invalidMaskInt()
    {
        return [
            [3.14],
            [Mask::MAX_INT + 1],
            [Mask::MIN_INT - 1],
            [3232236033]  # 192.168.2.1
        ];
    }

    /**
     * @param int $ip_int
     * @dataProvider invalidMaskInt
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidIp($ip_int)
    {
        $mask = new Mask($ip_int, Mask::TYPE_INT);
    }

    public function validMaskInt()
    {
        return [
            [$this->mask_source_int],
            [Mask::MAX_INT - 1],
            [Mask::MIN_INT + 1],
        ];
    }

    /**
     * @param int $ip_int
     * @dataProvider validMaskInt
     */
    public function testValidIp($ip_int)
    {
        $mask = new Mask($ip_int, Mask::TYPE_INT);
        $this->assertTrue($mask->asInt() == $ip_int);
    }


    public function invalidMaskString()
    {
        return [
            ['192.168.2.1'],
            ['255.255.255.255.255'],
            ['someString']
        ];
    }

    /**
     * @param string $mask_string
     * @dataProvider invalidMaskString
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidIpString($mask_string)
    {
        $mask = new Mask($mask_string, Mask::TYPE_STRING);
    }

    public function validMaskString()
    {
        return [
            ['255.255.255.0'],
            ['255.255.255.255'],
            ['255.255.255.240'],
            ['255.192.0.0'],
        ];
    }

    /**
     * @param string $mask_string
     * @dataProvider validMaskString
     */
    public function testValidMaskString($mask_string)
    {
        $mask = new Mask($mask_string, Mask::TYPE_STRING);
        $this->assertTrue($mask->asString() == $mask_string);
        $this->assertTrue((string) $mask == $mask_string);
    }

    public function invalidMaskPrefix()
    {
        return [
            [33],
            [0],
            [-1]
        ];
    }

    /**
     * @param string $mask_prefix
     * @dataProvider invalidMaskPrefix
     * @expectedException LTDBeget\Network\exception\IpNotValidException
     */
    public function testInvalidMaskPrefix($mask_prefix)
    {
        $mask = new Mask($mask_prefix, Mask::TYPE_STRING);
    }

    public function validMaskPrefix()
    {
        return [
            [32],
            [29],
            [8],
        ];
    }

    /**
     * @param string $mask_prefix
     * @dataProvider validMaskPrefix
     */
    public function testValidMaskPrefix($mask_prefix)
    {
        $mask = new Mask($mask_prefix);
        $this->assertTrue($mask->asPrefix() == $mask_prefix);
    }


    public function testCountIpInMask()
    {
        $mask = new Mask(32);
        $this->assertTrue($mask->countIp() == 1);

        $mask = new Mask(31);
        $this->assertTrue($mask->countIp() == 2);

        $mask = new Mask(30);
        $this->assertTrue($mask->countIp() == 4);

        $mask = new Mask(20);
        $this->assertTrue($mask->countIp() == 4096);
    }
}
