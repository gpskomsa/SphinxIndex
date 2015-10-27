<?php

namespace SphinxIndex\Index;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Di\Di;

use SphinxIndex\Options\ModuleOptions;

/**
 * @todo rework it to abstract factory
 */
class IndexFactory implements IndexFactoryInterface, ServiceManagerAwareInterface
{
    /**
     *
     * @var ServiceManager
     */
    protected $serviceManager = null;

    /**
     *
     * @var ModuleOptions
     */
    protected $moduleOptions = null;

    /**
     *
     * @param ModuleOptions $moduleOptions
     */
    public function __construct(ModuleOptions $moduleOptions)
    {
        $this->moduleOptions = $moduleOptions;
    }

    /**
     *
     * @param ServiceManager $serviceManager
     * @return IndexFactory
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * Main method, returns object of Index
     *
     * @param string $indexName
     * @return Index
     */
    public function getIndex($indexName)
    {
        $dic = $this->getInitializedDic($indexName);
        $index = $dic->get('SphinxIndex\Index\Index', array(
            'indexName' => $indexName,
            'searchdConfig' => $this->serviceManager->get('SearchdConfig'),
            'indexerConfig' => $this->serviceManager->get('IndexerConfig'),
            'mainIndex' => $this->getMainIndex($indexName),
            'serverId' => $this->serviceManager
                ->get('SphinxConfigModuleOptions')
                ->getConfigId(),
            'serviceManager' => $this->serviceManager,
        ));

        return $index;
    }

    /**
     * Makes Di instance for index $indexName
     *
     * @param string $indexName
     * @return Di
     */
    public function getInitializedDic($indexName)
    {
        $params = $this->getConfigOptionsFor($indexName);
        $dic = new Di();
        if (isset($params['instance'])) {
            $this->prepareInstanceConfig($params['instance']);

            $dic->configure(
                new \Zend\Di\Config(
                    array(
                        'definition' => array(),
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
     */
    protected function prepareInstanceConfig(array &$config)
    {
        foreach ($config as &$value) {
            if (is_array($value) && count($value) == 1 && isset($value['valueAsService'])) {
                $value = $this->serviceManager->get($value['valueAsService']);
            } else if (is_array($value)) {
                $this->prepareInstanceConfig($value);
            }
        }
    }

    /**
     * Can index be created?
     *
     * @param string $indexName
     * @return boolean
     */
    public function canCreate($indexName)
    {
        $configName = $this->getConfigName($indexName);
        $config = $this->moduleOptions->getIndexFactory();
        if (!isset($config['indexes'][$configName])) {
            return false;
        }

        return true;
    }

    /**
     * Returns distributed index name if source index name is chunk of someone
     *
     * @todo rename function to appropriate name
     * @param string $indexName
     * @return string|false
     */
    protected function getConfigName($indexName)
    {
        $indexerConfig = $this->serviceManager->get('IndexerConfig');
        $config = $this->moduleOptions->getIndexFactory();

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
     * @return string
     */
    public function getMainIndex($indexName)
    {
        $params = $this->getConfigOptionsFor($indexName);

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
     * @return array
     */
    protected function getConfigOptionsFor($indexName)
    {
        if (!$this->canCreate($indexName)) {
            throw new \Exception('options for ' . $indexName . ' not defined in config');
        }

        $configName = $this->getConfigName($indexName);

        $config = $this->moduleOptions->getIndexFactory();
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