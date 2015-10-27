<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class StringToCrc32 implements FilterInterface
{
    /**
     * Returns crc32 of value
     *
     * @param string $value
     * @return int
     */
    public function filter($value)
    {
        return crc32($value);
    }
}