<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class SpaceToUnderscore implements FilterInterface
{
    /**
     * Space to underscope
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        return str_replace(' ', '_', $value);
    }
}