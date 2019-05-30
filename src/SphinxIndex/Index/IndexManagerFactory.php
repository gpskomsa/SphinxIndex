<?php

declare(strict_types=1);

namespace SphinxIndex\Index;

use Psr\Container\ContainerInterface;
use Zend\ServiceManager\Config;

class IndexManagerFactory
{
    public function __invoke(ContainerInterface $container) : IndexManager
    {
        $manager = new IndexManager($container);

        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['sphinxindex_index_manager_config'] ?? [];

        if (!empty($config)) {
            (new Config($config))->configureServiceManager($manager);
        }

        return $manager;
    }
}
