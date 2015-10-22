<?php

namespace SphinxIndex\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Db\Adapter\Adapter;

class DbAdapter implements ServiceManagerAwareInterface
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
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (!isset($this->stack[$name])) {
            $config = $this->serviceManager->get('config');

            if (!isset($config['storage']['adapter'][$name])) {
                throw new \Exception('adapter config for ' . $name . ' not defined');
            }

            $options = $config['storage']['adapter'][$name];
            $adapter = new Adapter($options);

            $this->stack[$name] = $adapter;
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
     * @return DbAdapter
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}