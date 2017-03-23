<?php

namespace SphinxIndex\DataProvider;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\Storage\ControlPointUsingInterface;
use SphinxIndex\Entity\DocumentSet;

class DataProvider implements DataProviderInterface
{
    /**
     * Storage object
     *
     * @var StorageInterface
     */
    protected $storage = null;

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
     * @param StorageInterface $storage
     * @param array $options
     * @param integer $chunkId
     */
    public function __construct(
        StorageInterface $storage,
        array $options = array()
    )
    {
        $this->setStorage($storage);
        $this->setOptions($options);
    }

    /**
     *
     * @param array $options
     * @return DataProvider
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
     * @param array|ListenerAggregateInterface $listeners
     * @return DataProvider
     */
    public function attachListeners($listeners)
    {
        if (is_string($listeners)) {
            $listeners = array($listeners => array());
        }

        if (!is_array($listeners)) {
            $listeners = (array) $listeners;
        }

        foreach ($listeners as $key => $listener) {
            if (is_string($key)) {
                if ($this->getPluginManager()->has($key)) {
                    $listener = $this->getPluginManager()->get(
                        $key,
                        is_array($listener) ? $listener : array()
                    );
                } elseif (class_exists($key)) {
                    if (is_array($listener)) {
                        $ref = new \ReflectionClass($key);
                        $listener = $ref->newInstanceArgs(array($listener));
                    } else {
                        $listener = new $key();
                    }
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'Expecting string listener to be valid class name; received "%s"',
                            $key
                        )
                    );
                }
            }

            if (!$listener instanceof ListenerAggregateInterface) {
                throw new \RuntimeException(
                    'listener is not instance of ListenerAggregateInterface'
                );
            }

            $this->getEventManager()->attachAggregate($listener);
        }

        return $this;
    }

    /**
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        if (null === $this->plugins) {
            $this->setPluginManager(new PluginManager());
        }

        return $this->plugins;
    }

    /**
     *
     * @param  PluginManager $plugins
     * @return DataProvider
     */
    public function setPluginManager(PluginManager $plugins)
    {
        $this->plugins = $plugins;
        $this->plugins->setDataProvider($this);

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
     * @return DataProvider
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
     * Returns prepared documents from storage to insert into index
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getDocumentsToInsert($chunkId = null)
    {
        return $this->processDocuments(false, $chunkId);
    }

    /**
     * Returns prepared documents from storage to update in index
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getDocumentsToUpdate($chunkId = null)
    {
        return $this->processDocuments(true, $chunkId);
    }

    /**
     *
     * @param boolean $update
     * @param integer $chunkId
     * @return DocumentSet
     */
    protected function processDocuments($update = false, $chunkId = null)
    {
        if ($update) {
            $documents = $this->storage->getItemsToUpdate($chunkId);
        } else {
            $documents = $this->storage->getItems($chunkId);
        }

        if ($documents) {
            $this->getEventManager()->trigger(
                $update ? self::EVENT_DOCUMENTS_TO_UPDATE : self::EVENT_DOCUMENTS_TO_INSERT,
                $this,
                array(
                    'documents' => $documents,
                )
            );
        } else {
            if ($this->storage instanceof ControlPointUsingInterface) {
                $this->storage->markControlPoint();
            }
        }

        return $documents;
    }

    /**
     * Returns prepared documents from storage to delete from index
     *
     * @param integer|null $chunkId
     * @return DocumentSet
     */
    public function getDocumentsToDelete($chunkId = null)
    {
        $documents = $this->storage->getItemsToDelete($chunkId);
        if ($documents) {
            $this->getEventManager()->trigger(
                self::EVENT_DOCUMENTS_TO_DELETE,
                $this,
                array(
                    'documents' => $documents,
                )
            );
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