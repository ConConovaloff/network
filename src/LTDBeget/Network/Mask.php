<?php

namespace LTDBeget\Network;
use LTDBeget\Network\exception\ApplicationException;
use LTDBeget\Network\exception\IpNotValidException;

/**
 * Толстая модель Маски.
 *
 * Теоретически существуют рваные маски в которых маска 255.255.0.255 ( discontiguous subnets ), но мы не думаем о них.
 */
class Mask extends Ip
{
    const TYPE_PREFIX = 'prefix';

    const MASK_PREFIX_MIN = 0;
    const MASK_PREFIX_MAX = 32;

    /**
     * Но существуют действующие реализации: rfc 3021
     * http://cisco-lab.by/study/articles/351-stat-ptp-link.html
     */
    const MASK_PREFIX_UNUSED = 31;

    /**
     * @var bool
     */
    protected $allow_31_prefix;


    /**
     * @var int Пример: 24
     */
    protected $mask_prefix;

    /**
     * @param string|int $mask
     * @param string     $type
     * @param bool       $allow_31_prefix
     *
     * @throws \Exception
     */
    public function __construct($mask, $type = Mask::TYPE_PREFIX, $allow_31_prefix = true)
    {
        $this->allow_31_prefix = $allow_31_prefix;

        switch ($type) {

            case Mask::TYPE_PREFIX:
                $this->setMaskPrefix((int)$mask);
                break;

            case Mask::TYPE_STRING:
                $this->setMaskString((string)$mask);
                break;

            case Mask::TYPE_INT:
                $this->setIpInt($mask);
                break;

            default:
                throw new IpNotValidException('Type not valid');
        }
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
        } elseif ($this->mask_prefix) {
            $this->setIpInt(self::prefixToInt($this->mask_prefix));
        } elseif ($this->ip_string) {
            $this->setIpInt(self::stringToInt($this->ip_string));
        } else {
            throw new ApplicationException('Not have mask');
        }

        return $this->ip_int;
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
        } elseif ($this->ip_int) {
            $this->setMaskString(self::intToString($this->ip_int));
        } elseif ($this->mask_prefix) {
            $this->setMaskString(self::prefixToString($this->mask_prefix));
        } else {
            throw new ApplicationException('Not have mask');
        }

        return $this->ip_string;
    }


    /**
     * @return int
     * @throws ApplicationException
     * @throws IpNotValidException
     */
    public function asPrefix()
    {
        if ($this->mask_prefix) {
            return $this->mask_prefix;
        } elseif ($this->ip_int) {
            $this->setMaskPrefix(self::intToPrefix($this->ip_int));
        } elseif ($this->ip_string) {
            $this->setMaskPrefix(self::stringToPrefix($this->ip_string));
        } else {
            throw new ApplicationException('Not have mask');
        }

        return $this->mask_prefix;
    }


    /**
     * @return string
     * @throws ApplicationException
     */
    public function __toString()
    {
        return $this->asString();
    }


    /**
     * @param string $mask_string
     * @throws exception\IpNotValidException
     */
    protected function setMaskString($mask_string)
    {
        if (!$this->validate_string($mask_string)) {
            throw new IpNotValidException('mask string is not valid: ' . (string) $mask_string);
        }

        $this->ip_string = $mask_string;
    }


    /**
     * @param int $mask_prefix
     * @throws \Exception
     */
    protected function setMaskPrefix($mask_prefix)
    {
        if (!$this->validate_prefix($mask_prefix)) {
            throw new IpNotValidException('mask prefix is not valid');
        }

        $this->mask_prefix = $mask_prefix;
    }

    /**
     * @param string $mask_string In: 255.255.255.0
     *                                        ['192.168.2.1'],
     * @return bool Out: true
     */
    protected function validate_string($mask_string)
    {
        if (! filter_var($mask_string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return $this->validate_int(self::stringToInt($mask_string));
    }


    /**
     * @param int $mask_int Пример: 3232236033
     * @return bool
     */
    protected function validate_int($mask_int)
    {
        # todo: do it right
        if (! preg_match('/^1*0*$/', decbin($mask_int))) {
            return false;
        }

        return parent::validate_int($mask_int);
    }


    /**
     * @param int $mask_prefix
     * @return bool
     */
    protected function validate_prefix($mask_prefix)
    {
        if (!is_int($mask_prefix)) {
            return false;
        }

        if (!(self::MASK_PREFIX_MIN <= $mask_prefix && $mask_prefix <= self::MASK_PREFIX_MAX)) {
            return false;
        }

        if (!$this->allow_31_prefix && $mask_prefix == self::MASK_PREFIX_UNUSED) {
            return false;
        }

        return true;
    }


    /**
     * @param int $prefix In: 24
     * @return int Out: 4294967040
     */
    public static function prefixToInt($prefix)
    {
        return pow(2, 32) - pow(2, (32 - $prefix));
    }


    /**
     * @param string $mask_string In: 255.255.255.0
     * @return int Out: 24
     */
    public static function stringToPrefix($mask_string)
    {
        return self::intToPrefix(ip2long($mask_string));
    }


    /**
     * Do I need to use Hamming_weight?
     * http://stackoverflow.com/questions/109023/how-to-count-the-number-of-set-bits-in-a-32-bit-integer
     *
     * or revert prefixToInt?
     *
     * @param int $mask_int In: 4294967040 (11111111111111111111111100000000 in binary)
     * @return int Out: 24
     */
    protected static function intToPrefix($mask_int)
    {
        $mask_bin_string = decbin($mask_int);
        return strlen(rtrim($mask_bin_string, '0'));
    }


    /**
     * @param int $mask_prefix In: 24
     * @return string Out: 255.255.255.0
     */
    public static function prefixToString($mask_prefix)
    {
        $mask_int = self::prefixToInt($mask_prefix);
        return self::intToString($mask_int);
    }


    /**
     * @param int $mask_int In: 4294967040
     * @return string Out: 255.255.255.0
     */
    public static function intToString($mask_int)
    {
        return long2ip($mask_int);
    }


    /**
     * @param string $mask_string In: 255.255.255.0
     * @return int Out: 4294967040
     */
    public static function stringToInt($mask_string)
    {
        return ip2long($mask_string);
    }


    /**
     * Количество всех Ip адресов в маске ()
     */
    public function countIp()
    {
        # We may calculate this by:
        $ip_int = $this->asInt();
        return ($ip_int ^ Network::MASK_32) + 1;

        # But we love our servers
        # todo: we love?
    }
}
