<?php

namespace SphinxIndex\DataDriver;

interface DataDriverInterface
{
    /**
     * Adds documents into index
     */
    public function addDocuments($data);

    /**
     * Deletes documents from index
     */
    public function removeDocuments($data);

    /**
     * DataDriver work initialization
     */
    public function init();

    /**
     * Finishes DataDriver's work
     */
    public function finish();
}