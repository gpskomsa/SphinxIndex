<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class StringToWordsCount implements FilterInterface
{
    /**
     * Returns word count of string
     *
     * @param string $value
     * @return int
     */
    public function filter($value)
    {
        if (preg_match_all('#\b([a-z0-9]+?)\b#ui', $value, $matches)) {
            return count($matches[1]);
        }

        return 0;
    }
}