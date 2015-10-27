<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class StripBadXmlUtf8 implements FilterInterface
{
    /**
     * Cuts off invalid utf8 for xml
     *
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $result = preg_replace(
            '/[^\x{09}\x{0A}\x{0D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            $value
        );

        return $result;
    }
}
