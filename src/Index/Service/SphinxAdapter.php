<?php

namespace SphinxIndex\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Db\Adapter\Adapter;

class SphinxAdapter implements ServiceManagerAwareInterface
{
    /**
     *
     * @var array
     */
    protected $stack = array();

    /**
     *
     * @var ServiceManager
     */
    protected $serviceManager = null;

    /**
     *
     * @todo must be implemented
     * @param string $name
     * @return Adapter
     */
    public function get($name)
    {
        throw new \Exception('not implemented yet');

        if (!isset($this->stack[$name])) {
            // assign the adapter
        }

        return $this->stack[$name];
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     *
     * @param ServiceManager $serviceManager
     * @return SphinxAdapter
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}