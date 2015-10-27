<?php

namespace SphinxIndex\Redis\Adapter;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;

class AbstractFactory implements AbstractFactoryInterface
{
    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return boolean
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        if (isset($config[$requestedName])
            && is_array($config[$requestedName])
            && !empty($config[$requestedName])) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return Adapter
     * @throws \Exception
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator)[$requestedName];

        $port = 6379;
        if (isset($config['port'])) {
            $port = $config['port'];
        }

        $timeout = 0;
        if (isset($config['timeout'])) {
            $timeout = $config['timeout'];
        }

        $redis = new \Redis();
        $redis->connect($config['host'], $port, $timeout);

        if (isset($config['db'])) {
            $redis->select(intval($config['db']));
        }

        if (isset($config['options'])) {
            foreach ($config['options'] as $option => $value) {
                $redis->setOption($option, $value);
            }
        }

        return $redis;
    }

    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     */
    protected function getConfig(ServiceLocatorInterface $serviceLocator)
    {
        return $serviceLocator->get('SphinxIndexModuleOptions')->getRedisAdapter();
    }
}