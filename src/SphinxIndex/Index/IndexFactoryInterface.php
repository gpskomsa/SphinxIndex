<?php

namespace SphinxIndex\Index;

interface IndexFactoryInterface
{
    /**
     * Returns Index object by index name
     * @return Index
     */
    public function getIndex($indexName);

    /**
     * Can create Index by index name $indexName?
     * @return boolean
     */
    public function canCreate($indexName);
}
