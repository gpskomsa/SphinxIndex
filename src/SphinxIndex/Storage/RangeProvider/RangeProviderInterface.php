<?php

namespace SphinxIndex\Storage\RangeProvider;

use SphinxIndex\Storage\Chunks;

interface RangeProviderInterface
{
    /**
     * Returns range of document's id, min and max id for index
     *
     * @param integer $chunkId
     * @return array
     */
    public function getRange($chunkId);

    /**
     * Sets ranges for distributed index
     *
     * @param Chunks $ranges
     */
    public function setRange(Chunks $ranges);
}