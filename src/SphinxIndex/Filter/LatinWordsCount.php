<?php

namespace SphinxIndex\Filter;

use Zend\Filter\FilterInterface;

class LatinWordsCount implements FilterInterface
{
    /**
     *
     * @var integer
     */
    protected $latinLettersInWords = null;

    /**
     *
     * @param integer $latinLettersInWord
     */
    public function __construct($latinLettersInWord = 3)
    {
        $this->latinLettersInWords = (integer) $latinLettersInWord;
    }
    /**
     *
     * @param string $value
     * @return integer
     */
    public function filter($value)
    {
        preg_match_all(
            '/(\b[a-z0-9]*[a-z]{' . $this->latinLettersInWords . ',}[a-z0-9]*\b)/iums',
            $value,
            $matches
        );

        return count($matches[0]);
    }
}