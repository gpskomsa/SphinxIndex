<?php
namespace SphinxIndex;

use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;

class Module
{
    public function init(ModuleManager $moduleManager)
    {
        $sm = $moduleManager->getEvent()->getParam('ServiceManager');
        $sm->get('ServiceListener')->addServiceManager(
            DataProvider\PluginManager::class,
            'sphinxindex_data_provider_plugin_manager_config',
            DataProvider\PluginManagerProviderInterface::class,
            'getSphinxIndexDataProviderPluginConfig'
        );

        $sm->get('ServiceListener')->addServiceManager(
            Index\IndexManager::class,
            'sphinxindex_index_manager_config',
            Index\IndexManagerProviderInterface::class,
            'getSphinxIndexManagerConfig'
        );
    }

    public function onBootstrap(MvcEvent $e)
    {}

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getModuleDependencies()
    {
        return array(
            'SphinxConfig',
        );
    }

    public function getControllerConfig()
    {
        return [];
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                Service\RedisAdapter::class => function($sm) {
                    $instance = new Service\RedisAdapter();
                    return $instance;
                },
                Service\SphinxAdapter::class => function($sm) {
                    return new Service\SphinxAdapter();
                },
                Options\ModuleOptions::class => function($sm) {
                    $config = $sm->get('Config');
                    return new Options\ModuleOptions($config['sphinx_index'] ?? []);
                },
                DataProvider\PluginManager::class => DataProvider\PluginManagerFactory::class,
                Index\IndexManager::class => Index\IndexManagerFactory::class,
            ),
            'abstract_factories' => array(
                Redis\Adapter\AbstractFactory::class,
            ),
            'invokables' => array(
                'SphinxIndex\Redis' => '\Redis',
            ),
            'shared' => array(
                'SphinxIndex\Redis' => false,
            ),
        );
    }
}
