<?php
namespace Index;

use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleUsageProviderInterface
{
    public function init(ModuleManager $manager)
    {
    }

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

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'SphinxIndex\Index\Index' => function ($sm) {
                    $if = $sm->getServiceLocator()->get('SphinxIndex\IndexFactory');
                    $controller = new Controller\IndexController($if);
                    return $controller;
                },
                'SphinxIndex\Index\Split' => function ($sm) {
                    $if = $sm->getServiceLocator()->get('SphinxIndex\IndexFactory');
                    $controller = new Controller\SplitController($if);
                    return $controller;
                },
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
                'SphinxIndex\Service\DbAdapter' => function($sm) {
                    return new Service\DbAdapter();
                },
                'SphinxIndex\Service\SphinxAdapter' => function($sm) {
                    return new Service\SphinxAdapter();
                },
                'SphinxIndex\DataProviderPluginManager' => 'Index\DataProvider\Service\PluginManagerFactory',
            ),
            'invokables' => array(
                'SphinxIndex\Redis' => '\Redis',
                'SphinxIndex\IndexFactory' => 'Index\Index\IndexFactory',
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
