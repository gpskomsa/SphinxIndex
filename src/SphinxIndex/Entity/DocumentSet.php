<?php

namespace SphinxIndex\Entity;

class DocumentSet implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * Document set
     *
     * @var array
     */
    protected $data = array();

    /**
     *
     * @var Service\DocumentFactory
     */
    protected $factory = null;

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
        }
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
     * Sets data for set
     *
     * @param \Traversable|array $set
     * @return DocumentSet
     * @throws \Exception
     */
    public function set($set)
    {
        if (!is_array($set)
            && !$set instanceof \Traversable) {
            throw new \Exception('data must be accessible for foreach');
        }

        foreach ($set as $data) {
            $this->add($data);
        }

        return $this;
    }

    /**
     * Adds data to set, create Document if nessesary
     *
     * @param mixed $data
     * @return DocumentSet
     */
    public function add($data)
    {
        if ($data instanceof Document) {
            $item = $data;
        } else {
            $item = $this->getFactory()->create($data);
        }

        if ($item) {
            $this->data[] = $item;
        }

        return $this;
    }

    /**
     * Return data of set as array
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->data as $item) {
            $data[] = $item->getValues();
        }

        return $data;
    }

    /**
     * Resets data of set
     *
     * @return SimpleSet
     */
    public function reset()
    {
        $this->data = array();

        return $this;
    }

    /**
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     *
     * @return integer
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     *
     * @return mixed
     */
    public function rewind()
    {
        return reset($this->data);
    }

    /**
     *
     * @return boolean
     */
    public function valid()
    {
        return (bool) $this->current();
    }

    /**
     *
     * @param integer $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     *
     * @param integer $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     *
     * @param integer $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     *
     * @param integer $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
