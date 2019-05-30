<?php

namespace SphinxIndex\Index;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

class IndexManager extends AbstractPluginManager
{
    public function validate($instance)
    {
        if (! is_callable($instance) && ! $instance instanceof Index) {
            throw new InvalidServiceException(
                sprintf(
                    '%s can only create instances of %s and/or callables; %s is invalid',
                    get_class($this),
                    Index::class,
                    (is_object($instance) ? get_class($instance) : gettype($instance))
                )
            );
        }
    }
}