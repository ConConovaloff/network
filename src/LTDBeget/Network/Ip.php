<?php

namespace LTDBeget\Network;

use LTDBeget\Network\exception\ApplicationException;
use LTDBeget\Network\exception\IpNotValidException;

class Ip
{
    /**
     * Представление в виде строки. Например: 192.168.2.1
     */
    const TYPE_STRING = 'string';

    /**
     * Представление в виде int. Например: 3232236033 (для 192.168.2.1).
     * Получение бинарной строки (string) '1001101...' поулучаем преобразованием int через decbin()
     */
    const TYPE_INT = 'int';

    /**
     * 255.255.255.255
     */
    const MAX_INT = 4294967295;

    /**
     * 0.0.0.0
     */
    const MIN_INT = 0;

    /**
     * @var string
     */
    protected $ip_string;

    /**
     * @var int
     */
    protected $ip_int;

    /**
     * @param string|int $ip
     * @param string     $type
     *
     * @throws IpNotValidException
     * @throws \InvalidArgumentException
     */
    public function __construct($ip, $type = Ip::TYPE_STRING)
    {
        switch ($type) {

            case Ip::TYPE_STRING:
                $this->setIpString((string)$ip);
                break;

            case Ip::TYPE_INT:
                $this->setIpInt($ip);
                break;

            default:
                throw new \InvalidArgumentException('Ip type not in valid range');
        }
    }


    /**
     * @return string
     * @throws ApplicationException
     * @throws IpNotValidException
     */
    public function asString()
    {
        if ($this->ip_string) {
            return $this->ip_string;
        }

        if (!$this->ip_int) {
            throw new ApplicationException('Not have ip');
        }

        $this->setIpString(long2ip($this->ip_int));

        return $this->ip_string;
    }


    /**
     * @return int
     * @throws ApplicationException
     * @throws IpNotValidException
     */
    public function asInt()
    {
        if ($this->ip_int) {
            return $this->ip_int;
        }

        if (!$this->ip_string) {
            throw new ApplicationException('Not have ip');
        }

        $this->setIpInt(ip2long($this->ip_string));

        return $this->ip_int;
    }


    /**
     * @param string $ip_string example: 192.168.2.1
     * @throws IpNotValidException
     */
    protected function setIpString($ip_string)
    {
        if (!$this->validate_string($ip_string)) {
            throw new IpNotValidException('ip address string is not valid: ' . (string) $ip_string);
        }

        $this->ip_string = $ip_string;
    }


    /**
     * @param int $ip_int example: 3232236033
     * @throws IpNotValidException
     */
    protected function setIpInt($ip_int)
    {
        if (!$this->validate_int($ip_int)) {
            throw new IpNotValidException('ip address int is not valid: ' . (string) $ip_int);
        }

        $this->ip_int = $ip_int;
    }


    /**
     * @param string $ip_string example: 192.168.2.1
     * @return bool
     */
    protected function validate_string($ip_string)
    {
        return (bool) filter_var($ip_string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * @param int $ip_int example: 3232236033
     * @return bool
     */
    protected function validate_int($ip_int)
    {
        if (!is_int($ip_int)) {
            return false;
        }

        if (!(self::MIN_INT <= $ip_int && $ip_int <= self::MAX_INT)) {
            return false;
        }

        return true;
    }
}
