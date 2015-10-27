<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class StringToUnixtimestamp implements FilterInterface
{
    /**
     * Try to convert string of date/time to unix timestamp
     *
     * @param string $value
     * @return int
     */
    public function filter($value)
    {
        if (!is_numeric($value) && is_string($value)) {
            $value = @strtotime($value);
        }

        if (!$value) {
            $value = time();
        }

        return $value;
    }
}