<?php

namespace SphinxIndex\Storage\Mysql\Chunk;

use SphinxIndex\Storage\Mysql\Chunk\Query;
use SphinxIndex\Storage\Chunks;

class QuerySplitter implements SplitterInterface
{
    /**
     *
     * @var Query
     */
    protected $query = null;

    /**
     *
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function split($chunkCount, $lastIsEmpty)
    {
        $rowCount = $this->query->getTotalCount();
        $chunks = new Chunks();

        if(!$rowCount) {
            return $chunks;
        }

        $realCount = $lastIsEmpty ? ($chunkCount - 1) : $chunkCount;
        $countForChunk = (integer) floor($rowCount / $realCount);

        $chunks->reset();
        $chunks->setCountForChunk($countForChunk);
        $min = 0;
        for ($i = 0; $i < $chunkCount; $i++) {
            $max = $this->query->getMaxIdForChunk($countForChunk, $min);
            if ($i + 1 >= $chunkCount) {
                $max = -1;
            }
            $chunks[] = array($min, $max);
            if (-1 === $max) {
                break;
            }

            $min = $max;
        }

        return $chunks;
    }
}
