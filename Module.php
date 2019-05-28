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
            'SphinxIndex\DataProvider\PluginManager',
            'sphinxindex_data_provider_plugin_manager_config',
            'SphinxIndex\DataProvider\Service\PluginManagerProviderInterface',
            'getSphinxIndexDataProviderPluginConfig'
        );

        $sm->get('ServiceListener')->addServiceManager(
            'SphinxIndex\Index\IndexManager',
            'sphinxindex_index_manager_config',
            'SphinxIndex\Index\IndexManagerProviderInterface',
            'getSphinxIndexManagerConfig'
        );
    }

    public function onBootstrap(MvcEvent $e)
    {}

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
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
                'SphinxIndex\Service\RedisAdapter' => function($sm) {
                    $instance = new Service\RedisAdapter();
                    return $instance;
                },
                'SphinxIndex\Service\SphinxAdapter' => function($sm) {
                    return new Service\SphinxAdapter();
                },
                'SphinxIndexModuleOptions' => function($sm) {
                    $config = $sm->get('Config');
                    return new Options\ModuleOptions(isset($config['sphinx_index']) ? $config['sphinx_index'] : array());
                },
                'SphinxIndex\DataProvider\PluginManager' => 'SphinxIndex\DataProvider\Service\PluginManagerFactory',
            ),
            'abstract_factories' => array(
                'SphinxIndex\Redis\Adapter\AbstractFactory',
            ),
            'invokables' => array(
                'SphinxIndex\Redis' => '\Redis',
                'SphinxIndex\Index\IndexManager' => 'SphinxIndex\Index\IndexManager',
            ),
            'shared' => array(
                'SphinxIndex\Redis' => false,
            ),
        );
    }
}
