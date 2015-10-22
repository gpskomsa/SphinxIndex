<?php

namespace SphinxIndex\Storage\Mysql\Chunk;

interface QueryInterface
{
    /**
     * Returns total document count
     *
     * @return integer
     */
    public function getTotalCount();

    /**
     * Returns max id for select chunk by his $minId
     *
     * @param integer $countForChunk count of documents in chunk
     * @param integer $minId min id of document in chunk
     * @return integer
     */
    public function getMaxIdForChunk($countForChunk, $minId);
}