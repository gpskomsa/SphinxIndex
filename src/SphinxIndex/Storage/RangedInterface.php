<?php

namespace SphinxIndex\Storage;

use SphinxIndex\Storage\RangeProvider\RangeProviderInterface;

/**
 * @todo split interface into two separate: Aware and Split?
 */
interface RangedInterface
{
    /**
     *
     * @param RangeProviderInterface $ranger
     */
    public function setRangeProvider(RangeProviderInterface $ranger);

    /**
     * Split indexing data into equal chunks
     *
     * @param integer $count
     * @return array
     */
    public function split($count);
}