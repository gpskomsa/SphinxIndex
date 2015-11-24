<?php

namespace SphinxIndex\Index;

use Zend\ServiceManager\AbstractPluginManager;

class IndexManager extends AbstractPluginManager
{
    /**
     * Detects if plugin is valid
     *
     * @param mixed $plugin
     * @return void
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof Index) {
            return;
        }

        throw new \Exception(
            sprintf(
                'Index of type %s is invalid; must be instance of or extend %s\Index',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
                __NAMESPACE__
            )
        );
    }
}