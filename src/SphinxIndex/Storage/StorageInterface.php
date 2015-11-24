<?php

namespace SphinxIndex\Storage;

use SphinxIndex\Entity\DocumentSet;

interface StorageInterface
{
    /**
     * Returns all data by equal portions for each request. False if there is no data.
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getItems($chunkId = null);

    /**
     * Returns data to update by equal portions for each request. False if there is no data.
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getItemsToUpdate($chunkId = null);

    /**
     * Returns data to delete by equal portions for each request. False if there is no data.
     * Only unique id required for every document data.
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getItemsToDelete($chunkId = null);
}