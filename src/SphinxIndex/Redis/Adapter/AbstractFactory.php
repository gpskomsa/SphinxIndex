<?php

namespace SphinxIndex\Redis\Adapter;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AbstractFactory implements AbstractFactoryInterface
{
    /**
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
     * @return \Redis
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getConfig($container)[$requestedName];

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
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return boolean
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $this->getConfig($container);
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
     * @return boolean
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->canCreate($serviceLocator, $requestedName);
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
        return $this($serviceLocator, $requestedName);
    }

    /**
     *
     * @param ContainerInterface $container
     * @return array
     */
    protected function getConfig(ContainerInterface $container)
    {
        return $container->get('SphinxIndexModuleOptions')->getRedisAdapter();
    }
}
