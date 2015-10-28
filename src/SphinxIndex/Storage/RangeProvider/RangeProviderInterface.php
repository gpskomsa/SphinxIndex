<?php

namespace SphinxIndex\Storage\RangeProvider;

use SphinxIndex\Storage\Chunks;

interface RangeProviderInterface
{
    /**
     * Returns range of document's id, min and max id for index
     * @return array
     */
    public function getRange();

    /**
     * Sets ranges for distributed index
     * @param Chunks $ranges
     */
    public function setRange(Chunks $ranges);
}