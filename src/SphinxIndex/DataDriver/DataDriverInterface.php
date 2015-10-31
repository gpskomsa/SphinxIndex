<?php

namespace SphinxIndex\DataDriver;

use SphinxIndex\Entity\DocumentSet;

interface DataDriverInterface
{
    /**
     * Adds documents into index
     * @param DocumentSet $documents
     */
    public function addDocuments(DocumentSet $documents);

    /**
     * Deletes documents from index
     * @param DocumentSet $documents
     */
    public function removeDocuments(DocumentSet $documents);

    /**
     * DataDriver work initialization
     */
    public function init();

    /**
     * Finishes DataDriver's work
     */
    public function finish();
}