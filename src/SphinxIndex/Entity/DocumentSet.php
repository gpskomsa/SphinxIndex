<?php

namespace SphinxIndex\Entity;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

class DocumentSet implements \Iterator
{
    const EVENT_DOCUMENT_CREATE = 'create';

    /**
     * Source data
     *
     * @var \Iterator
     */
    protected $data = null;

    /**
     *
     * @var Service\DocumentFactory
     */
    protected $factory = null;

    /**
     *
     * @var EventManagerInterface
     */
    protected $events;

    /**
     *
     * @var Document
     */
    protected $current = null;

    /**
     *
     * @var integer
     */
    protected $countProcessed = 0;

    /**
     *
     * @param mixed $data
     * @param Service\DocumentFactory $factory
     */
    public function __construct($data = null, Service\DocumentFactory $factory = null)
    {
        if (null !== $factory) {
            $this->factory = $factory;
        }

        if (null !== $data) {
            $this->set($data);
        } else {
            $this->data = new \ArrayIterator(array());
        }
    }

    /**
     *
     * @param  EventManagerInterface $events
     * @return DocumentSet
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
     * @return Service\DocumentFactory
     */
    public function getFactory()
    {
        if (null === $this->factory) {
            $this->setFactory(new Service\DocumentFactory());
        }

        return $this->factory;
    }

    /**
     *
     * @param Service\DocumentFactory $factory
     * @return DocumentSet
     */
    public function setFactory(Service\DocumentFactory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Sets data for documents
     *
     * @param \Traversable|array $set
     * @return DocumentSet
     * @throws \Exception
     */
    public function set($set)
    {
        if (is_array($set)) {
            $this->data = new \ArrayIterator($set);
        } else if ($set instanceof \IteratorAggregate) {
            $this->data = $set->getIterator();
        } else if ($set instanceof \Iterator) {
            $this->data = $set;
        } else {
            throw new \Exception('data must be accessible for foreach');
        }

        return $this;
    }

    /**
     *
     * @param mixed $data
     * @return Document
     */
    public function createDocument($data)
    {
        $document = $this->getFactory()->create($data);
        $this->getEventManager()->trigger(
            self::EVENT_DOCUMENT_CREATE,
            $this,
            array('document' => $document)
        );

        return $document;
    }

    /**
     * Get count of processed(created) documents
     *
     * @return integer
     */
    public function getCountProcessed()
    {
        return $this->countProcessed;
    }

    /**
     *
     * @return Document|false
     */
    public function current()
    {
        if (null !== $this->current) {
            return $this->current;
        }

        $data = $this->data->current();
        if (!$data) {
            return false;
        }

        $this->countProcessed++;
        $this->current = $this->createDocument($data);

        return $this->current;
    }

    /**
     *
     * @return integer
     */
    public function key()
    {
        return $this->data->key();
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        $this->current = null;

        return $this->data->next();
    }

    /**
     *
     * @return mixed
     */
    public function rewind()
    {
        $this->countProcessed = 0;
        $this->current = null;

        return $this->data->rewind();
    }

    /**
     *
     * @return boolean
     */
    public function valid()
    {
        return (bool) $this->current();
    }
}
