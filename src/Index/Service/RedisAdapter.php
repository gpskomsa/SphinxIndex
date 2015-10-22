<?php

namespace SphinxIndex\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

class RedisAdapter implements ServiceManagerAwareInterface
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
            $redis = $this->serviceManager->get('Redis');
            $config = $this->serviceManager->get('config');

            if (!isset($config['redis'][$name])) {
                throw new \Exception('redis config for ' . $name . ' not defined');
            }

            $options = $config['redis'][$name];
            if (!isset($options['host'])) {
                throw new \Exception('host parameter is required');
            }

            $port = 6379;
            if (isset($options['port'])) {
                $port = $options['port'];
            }

            $timeout = 0;
            if (isset($options['timeout'])) {
                $timeout = $options['timeout'];
            }

            $redis->connect($options['host'], $port, $timeout);

            if (isset($options['db'])) {
                $redis->select(intval($options['db']));
            }

            if (isset($options['options'])) {
                foreach ($options['options'] as $option => $value) {
                    $redis->setOption($option, $value);
                }
            }

            $this->stack[$name] = $redis;
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
     * @return RedisAdapter
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}