<?php

namespace SphinxIndex\DataProvider;

use Zend\EventManager\EventManagerInterface;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\DataProvider\PluginManager;

interface DataProviderInterface
{
    const EVENT_DOCUMENTS_TO_INSERT = 'documentsToInsert';
    const EVENT_DOCUMENTS_TO_UPDATE = 'documentsToUpdate';
    const EVENT_DOCUMENTS_TO_DELETE = 'documentsToDelete';

    /**
     * Returns documents to insert into index
     *
     * @param integer|null $chunkId
     */
    public function getDocumentsToInsert($chunkId = null);

    /**
     * Returns documents to update in index
     *
     * @param integer|null $chunkId
     */
    public function getDocumentsToUpdate($chunkId = null);

    /**
     * Returns documents to delete from index
     *
     * @param integer|null $chunkId
     */
    public function getDocumentsToDelete($chunkId = null);

    /**
     * Returns object of StorageInterface
     * @return StorageInterface
     */
    public function getStorage();

    /**
     * @return PluginManager
     */
    public function getPluginManager();

    /**
     * @return EventManagerInterface
     */
    public function getEventManager();
}
