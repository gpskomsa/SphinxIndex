<?php

namespace SphinxIndex\Storage;

class Chunks implements \Iterator, \Countable, \ArrayAccess
{
    /**
     *
     * @var array
     */
    protected $ranges = array();

    /**
     *
     * @var integer
     */
    protected $countForChunk = null;

    /**
     *
     * @return integer
     */
    public function getAvgCountForChunkByRanges()
    {
        if (!count($this)) {
            return 0;
        }

        $total = array();
        foreach ($this as $chunk) {
            if ($chunk[1] > $chunk[0]) {
                $total[] = ($chunk[1] - $chunk[0]);
            }
        }

        return (integer) floor(array_sum($total)/count($total));
    }

    /**
     *
     * @param integer $value
     * @return \Index\Storage\Chunks
     */
    public function setCountForChunk($value)
    {
        $this->countForChunk = (integer) $value;

        return $this;
    }

    /**
     *
     * @return integer
     */
    public function getCountForChunk()
    {
        return $this->countForChunk;
    }

    /**
     *
     * @return \Index\Storage\Mysql\Chunk\Chunks
     */
    public function reset()
    {
        $this->ranges = array();

        return $this;
    }

    /**
     *
     * @return integer
     */
    public function count()
    {
        return count($this->ranges);
    }

    /**
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->ranges);
    }

    /**
     *
     * @return integer
     */
    public function key()
    {
        return key($this->ranges);
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->ranges);
    }

    /**
     *
     * @return mixed
     */
    public function rewind()
    {
        return reset($this->ranges);
    }

    /**
     *
     * @return boolean
     */
    public function valid()
    {
        return (bool) $this->current();
    }

    /**
     *
     * @param integer $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->ranges[] = $value;
        } else {
            $this->ranges[$offset] = $value;
        }
    }

    /**
     *
     * @param integer $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->ranges[$offset]);
    }

    /**
     *
     * @param integer $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->ranges[$offset]);
    }

    /**
     *
     * @param integer $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->ranges[$offset]) ? $this->ranges[$offset] : null;
    }
}
