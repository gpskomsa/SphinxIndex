<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class NotAlnumToSpace implements FilterInterface
{
    /**
     * @var bool
     */
    protected $unicode = false;

    /**
     * @param bool $unicode
     */
    public function __construct($unicode = false)
    {
        $this->unicode = $unicode;
    }

    /**
     * Replace all not [a-zA-Z0-9_] to space
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        return preg_replace(
            $this->unicode ? '/[^\p{L}\p{N}]+/u' : '/[^\w]+/',
            ' ',
            $value
        );
    }
}