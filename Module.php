<?php
namespace SphinxIndex;

use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;

class Module implements ConsoleUsageProviderInterface
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
        return array(
            'invokables' => array(
                'SphinxIndex\Index' => 'SphinxIndex\Controller\IndexController',
                'SphinxIndex\Split' => 'SphinxIndex\Controller\SplitController',
            ),
        );
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

    public function getConsoleUsage(Console $console)
    {
        return array(
            'To set/update documents into sphinx\'s index:',
            'index build <index_name>' => 'build index <index_name>, really only affects on RT-index',
            'index update <index_name>' => 'update index <index_name>, really only affects on RT-index',
            array('<index_name>', 'real index name, not distributed'),
            'To split one distributed index document\'s id range into equal chunks:',
            'index split <index_name>' => 'split index `index_name`',
            array('<index_name>', 'distributed index name'),
        );
    }
}
