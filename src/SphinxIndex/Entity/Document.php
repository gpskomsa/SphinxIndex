<?php

namespace SphinxIndex\Entity;

class Document
{
    /**
     *
     * @var array
     */
    protected $data = array();

    /**
     *
     * @var string
     */
    protected $keyName = 'id';

    /**
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        if (!empty($data)) {
            $this->exchangeArray($data);
        }
    }

    /**
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     *
     * @param type $keyName
     * @return Document
     */
    public function setKeyName($keyName)
    {
        $this->keyName = $keyName;

        return $this;
    }

    /**
     *
     * @return mixed
     */
    public function getKeyValue()
    {
        return $this->{$this->getKeyName()};
    }

    /**
     * 
     * @param array $data
     * @return array
     */
    public function exchangeArray(array $data)
    {
        $old = $this->getValues();
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }

        return $old;
    }

    /**
     *
     * @return array
     */
    public function getValues()
    {
        return $this->data;
    }

    /**
     *
     * @param string $name
     * @param string|integer|float $value
     * @return Document
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (isset($this->$name)) {
            $this->data[$name] = null;
        }
    }
}