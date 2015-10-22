<?php

namespace SphinxIndex\Storage;

use SphinxIndex\Service\RangeProviderInterface;

/**
 * @todo split interface into two separate: Aware and Split?
 */
interface RangedInterface
{
    public function setRangeProvider(RangeProviderInterface $ranger);

    /**
     * Split indexing data into equal chunks
     *
     * @param integer $count
     * @return array
     */
    public function split($count);
}