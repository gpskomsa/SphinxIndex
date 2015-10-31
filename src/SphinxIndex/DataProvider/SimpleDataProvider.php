<?php

namespace SphinxIndex\DataProvider;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\DataDriver\DataDriverInterface;
use SphinxIndex\Storage\ControlPointUsingInterface;

use SphinxIndex\Entity\DocumentSet;

class SimpleDataProvider implements DataProviderInterface, ServiceManagerAwareInterface
{
    /**
     * Storage object
     *
     * @var StorageInterface
     */
    protected $storage = null;

    /**
     * Driver object
     *
     * @var DataDriverInterface
     */
    protected $dataDriver = null;

    /**
     *
     * @var PluginManager
     */
    protected $plugins = null;

    /**
     *
     * @var EventManagerInterface
     */
    protected $events;

    /**
     *
     * @var ServiceManager
     */
    protected $serviceManager = null;

    /**
     *
     * @var array
     */
    protected $pluginOptions = array();

    /**
     *
     * @param StorageInterface $storage
     * @param DataDriverInterface $driver
     */
    public function __construct(
        StorageInterface $storage,
        DataDriverInterface $dataDriver,
        array $options = array()
    )
    {
        $this->setStorage($storage);
        $this->setDataDriver($dataDriver);

        $this->setOptions($options);
    }

    /**
     *
     * @param array $options
     * @return SimpleDataProvider
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array($value));
            }
        }

        return $this;
    }

    /**
     *
     * @param ServiceManager $serviceManager
     * @return SimpleDataProvider
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        if (null === $this->plugins) {
            $this->setPluginManager($this->serviceManager->get('SphinxIndex\DataProviderPluginManager'));
        }

        return $this->plugins;
    }

    /**
     *
     * @param  PluginManager $plugins
     * @return SimpleDataProvider
     */
    public function setPluginManager(PluginManager $plugins)
    {
        $this->plugins = $plugins;
        $this->plugins->setDataProvider($this);

        return $this;
    }

    /**
     *
     * @param array $pluginOptions
     * @return SimpleDataProvider
     */
    public function setPluginOptions(array $pluginOptions)
    {
        $this->pluginOptions = $pluginOptions;

        return $this;
    }

    /**
     * Proxy for plugin call
     *
     * @param string $name
     * @param array $options
     * @return mixed
     */
    public function plugin($name, array $options = null)
    {
        if (null === $options && isset($this->pluginOptions[$name])) {
            $options = $this->pluginOptions[$name];
        }

        return $this->getPluginManager()->get($name, $options);
    }

    /**
     * if method is not defined try to call it as plugin
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        $plugin = $this->plugin($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }

        return $plugin;
    }

    /**
     *
     * @param  EventManagerInterface $events
     * @return SimpleDataProvider
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_called_class()
        ));

        $this->events = $events;

        return $this;
    }

    /**
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     * 
     * Gets data from storage, prepare it and transfer into DataDriver
     */
    public function setDocuments()
    {
        $this->processDocuments();
    }

    /**
     * 
     * Gets data from storage, prepare it and transfer into pool
     */
    public function updateDocuments()
    {
        $this->processDocuments(true);
    }

    /**
     *
     * @param boolean $update
     */
    protected function processDocuments($update = false)
    {
        $this->dataDriver->init();

        while($documents = $this->storage->getItems()) {
            $this->prepareDocuments($documents);
            $this->dataDriver->addDocuments($documents);
        }

        if ($update) {
            while ($documentsToDelete = $this->storage->getItemsToDelete()) {
                $this->dataDriver->removeDocuments($documentsToDelete);
            }
        }

        $this->dataDriver->finish();

        if ($this->storage instanceof ControlPointUsingInterface) {
            $this->storage->markControlPoint();
        }
    }

    /**
     *
     * @param DataDriverInterface $dataDriver
     */
    public function setDataDriver(DataDriverInterface $dataDriver)
    {
        $this->dataDriver = $dataDriver;
    }

    /**
     *
     * @param DocumentSet $documents
     * @return DocumentSet
     */
    protected function prepareDocuments(DocumentSet $documents)
    {
        foreach ($documents as $document) {
            $this->filters($document);
        }

        return $documents;
    }

    /**
     *
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }
}