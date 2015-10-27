<?php

namespace SphinxIndex\Storage;

interface StorageInterface
{
    /**
     * Returns all data by equal portions for each request. False if there is no data.
     */
    public function getItems();

    /**
     * Returns data to update by equal portions for each request. False if there is no data.
     */
    public function getItemsToUpdate();

    /**
     * Returns data to delete by equal portions for each request. False if there is no data.
     * Only unique id required for every document data.
     */
    public function getItemsToDelete();
}