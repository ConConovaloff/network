<?php

namespace LTDBeget\Network;


/**
 * Итератор по IpUsable
 *
 * Пример:
 *   Дано:
 *     - 192.168.2.0/23 (192.168.2.1 - 192.168.3.254)
 *     - В этой сети создана подсеть 192.168.2.4/30 (192.168.2.4 - 192.168.2.7) которая уже не является IpUsable
 *
 *   В итоге, мы должны пройтись по: 192.168.2.1 - 192.168.2.3 и 192.168.2.8 - 192.168.3.254
 */
class IpUsableRange implements \Iterator, \Countable
{
    /**
     * @var array
     */
    private $ip_int_array;

    /**
     * @var array
     */
    private $current_array;

    /**
     * @var int
     */
    private $current_position;

    /**
     * @var int
     */
    private $current_position_array;

    /**
     * @var int
     */
    private $current_value;

    /**
     * @var int
     */
    private $ip_start;

    /**
     * @var int
     */
    private $ip_end;


    /**
     * @param array $ip_int_array In: [ [3232236033, 3232236035], [3232236040, 3232236542] ... ]
     *                            Or: [3232236033, 3232236035]
     * @throws \Exception
     */
    public function __construct(array $ip_int_array)
    {
        if (is_int($ip_int_array[0])) {
            $this->ip_int_array = [];
            $this->ip_int_array[0] = $ip_int_array;

        } elseif(is_array($ip_int_array[0])) {
            $this->ip_int_array = $ip_int_array;

        } else {
            throw new \Exception('ip_int_array have wrong format');
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return IpUsable
     */
    public function current()
    {
        $ip = new Ip($this->current_value, IP::TYPE_INT);
        return new IpUsable($ip);
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        ++$this->current_position;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->current_position;
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
        $this->current_value = $this->ip_start + $this->current_position;

        if ($this->current_value > $this->ip_end) {
            ++$this->current_position_array;
            if (count($this->ip_int_array) >= $this->current_position_array) {
                return false;
            }

            $this->prepareNextArray($this->current_position_array);
            $this->valid();
        }

        return true;
    }

    public function prepareNextArray($position_array)
    {
        $this->current_position = 0;
        $this->current_position_array = $position_array;
        $this->current_array = $this->ip_int_array[$this->current_position_array];
        $this->ip_start = min($this->current_array);
        $this->ip_end = max($this->current_array);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->prepareNextArray(0);
    }


    /**
     * Вернет первый ip
     * @return Ip
     */
    public function getFirstIp()
    {
        $ip = new Ip(min($this->ip_int_array[0]), Ip::TYPE_INT);
        return new IpUsable($ip);
    }


    /**
     * Вернет последний ip
     * @return Ip
     */
    public function getLastIp()
    {
        $ip = new Ip(max(end($this->ip_int_array)), Ip::TYPE_INT);
        return new IpUsable($ip);
    }


    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        $result = 0;

        foreach ($this->ip_int_array as $ip_array) {
            $result += count($ip_array);
        }

        return $result;
    }
}
