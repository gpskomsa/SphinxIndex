<?php

namespace SphinxIndex\Storage\Mysql\Chunk;

use SphinxIndex\Storage\Chunks;

interface SplitterInterface
{
    /**
     *
     * @param integer $chunkCount
     * @param boolean $lastIsEmpty
     * @return Chunks
     */
    public function split($chunkCount, $lastIsEmpty);
}
