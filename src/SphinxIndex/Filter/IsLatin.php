<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class isLatin implements FilterInterface
{
    /**
     *
     * @param string $value
     * @return integer
     */
    public function filter($value)
    {
        if (preg_replace('/[\p{L}]+/iu', '', $value) === preg_replace('/[a-z]+/iu', '', $value)) {
            return 1;
        }

        return 0;
    }
}
