<?php

namespace SphinxIndex\DataProvider;

use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceManager;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\DataProvider\PluginManager;

interface DataProviderInterface
{
    /**
     * Sets documents of index
     */
    public function setDocuments();

    /**
     * Updates documents of index
     */
    public function updateDocuments();

    /**
     * Returns object of StorageInterface
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

    /**
     * @return ServiceManager
     */
    public function getServiceManager();
}
