<?php

namespace SphinxIndex\Index;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\Di\Di;

class IndexFactory implements AbstractFactoryInterface
{
    /**
     *
     * @var string
     */
    protected $match = '/^SphinxIndex\\\\Index\\\\Index\\\\([\w]+)$/i';
    
    /**
     * Main method, returns object of Index
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return Index
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $indexName = strtolower(preg_replace($this->match, '\1', $requestedName));
        $dic = $this->getInitializedDic($indexName, $serviceLocator);
        $index = $dic->get(
            'SphinxIndex\Index\Index', array(
            'indexName' => $indexName,
            'searchdConfig' => $serviceLocator->get('SearchdConfig'),
            'indexerConfig' => $serviceLocator->get('IndexerConfig'),
            'mainIndex' => $this->getMainIndex($indexName, $serviceLocator),
            'serverId' => $serviceLocator
                ->get('SphinxConfigModuleOptions')
                ->getConfigId(),
            'serviceManager' => $serviceLocator,
            )
        );

        return $index;
    }

    /**
     * Makes Di instance for index $indexName
     *
     * @param string $indexName
     * @param ServiceLocatorInterface $serviceLocator
     * @return Di
     */
    protected function getInitializedDic($indexName, ServiceLocatorInterface $serviceLocator)
    {
        $params = $this->getConfigOptionsFor($indexName, $serviceLocator);
        $dic = new Di();
        if (isset($params['instance'])) {
            $this->prepareInstanceConfig($params['instance'], $serviceLocator);

            $dic->configure(
                new \Zend\Di\Config(
                    array(
                        'instance' => $params['instance']
                    )
                )
            );
        }

        if (!isset($params['delta']) || !$params['delta']) {
            $indexType = Index::INDEX_TYPE_MAIN;
        } else {
            $indexType = Index::INDEX_TYPE_DELTA;
        }

        $dic->instanceManager()->setParameters(
            'SphinxIndex\Index\Index',
            array(
                'indexType' => $indexType
            )
        );

        return $dic;
    }

    /**
     *
     * @param array $config
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function prepareInstanceConfig(array &$config, ServiceLocatorInterface $serviceLocator)
    {
        foreach ($config as &$value) {
            if (is_array($value) && count($value) == 1 && isset($value['serviceManagerServiceName'])) {
                $value = $serviceLocator->get($value['serviceManagerServiceName']);
            } else if (is_array($value)) {
                $this->prepareInstanceConfig($value, $serviceLocator);
            }
        }
    }

    /**
     * Can index be created?
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return boolean
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!preg_match($this->match, $requestedName)) {
            return false;
        }

        $indexName = strtolower(preg_replace($this->match, '\1', $requestedName));
        $configName = $this->getConfigOptionNameFor($indexName, $serviceLocator);
        $config = $serviceLocator->get('SphinxIndexModuleOptions')->getIndexFactory();
        if (!isset($config['indexes'][$configName])) {
            return false;
        }

        return true;
    }

    /**
     * Returns distributed index name if source index name is chunk of someone
     *
     * @param string $indexName
     * @param ServiceLocatorInterface $serviceLocator
     * @return string|false
     */
    protected function getConfigOptionNameFor($indexName, ServiceLocatorInterface $serviceLocator)
    {
        $indexerConfig = $serviceLocator->get('IndexerConfig');
        $config = $serviceLocator->get('SphinxIndexModuleOptions')->getIndexFactory();

        $section = $indexerConfig->getSection('index', $indexName);
        if (!$section || !isset($config['indexes'][$section->getName()])) {
            $section = $indexerConfig->getDistributedFor($indexName);
        }

        if (!$section || !isset($config['indexes'][$section->getName()])) {
            return $indexName;
        }

        return $section->getName();
    }

    /**
     * Returns main index name if source index name is name of delta index
     * otherwise returns just source name
     *
     * @param string $indexName
     * @param ServiceLocatorInterface $serviceLocator
     * @return string
     */
    protected function getMainIndex($indexName, ServiceLocatorInterface $serviceLocator)
    {
        $params = $this->getConfigOptionsFor($indexName, $serviceLocator);

        if (!isset($params['delta']) || !$params['delta']) {
            $mainIndex = $indexName;
        } else {
            $mainIndex = preg_replace(
                $params['toMain'][0],
                $params['toMain'][1],
                $indexName
            );
        }

        return $mainIndex;
    }

    /**
     * Returns config parameters array for index name
     *
     * @param string $indexName
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     */
    protected function getConfigOptionsFor($indexName, ServiceLocatorInterface $serviceLocator)
    {
        $configName = $this->getConfigOptionNameFor($indexName, $serviceLocator);

        $config = $serviceLocator->get('SphinxIndexModuleOptions')->getIndexFactory();
        $params = $config['indexes'][$configName];
        if (isset($params['instanceExtends'])) {
            $params['instance'] = isset($params['instance']) ? $params['instance'] : array();
            $params['instance'] = $this->arrayMergeRecursive(
                $params['instance'],
                $config['indexes'][$params['instanceExtends']]['instance']
            );
        }

        if (isset($config['global'])) {
            $params = $this->arrayMergeRecursive(
                $config['global'],
                $params
            );
        }

        return $params;
    }

    /**
     * Merges two arrays recursively
     *
     * @param array $one
     * @param array $two
     * @return array
     */
    public function arrayMergeRecursive($one, $two)
    {
        foreach ($two as $key => $value)
        {
            if (array_key_exists($key, $one) && is_array($value)) {
                $one[$key] = $this->arrayMergeRecursive($one[$key], $two[$key]);
            } else {
                $one[$key] = $value;
            }
        }

        return $one;
    }
}