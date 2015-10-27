<?php

namespace SphinxIndex\Storage;

trait Stated
{
    /**
     * Array of states
     *
     * @var array
     */
    protected $state = array();

    /**
     * Sets and/or returns current state
     *
     * @param string $name
     * @param mixed $state
     * @return boolean
     */
    public function state($name, $state = null)
    {
        if (null !== $state) {
            $this->state[$name] = $state;
            return $state;
        } elseif (!isset($this->state[$name])) {
            return false;
        } else {
            return $this->state[$name];
        }
    }
}