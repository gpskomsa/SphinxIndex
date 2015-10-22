<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class NormalizeWhitespace implements FilterInterface
{
    /**
     * Trims whitespaces and replace their sequences to single one
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}