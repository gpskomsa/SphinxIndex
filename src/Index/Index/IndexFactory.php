<?php

namespace SphinxIndex\Index;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Di\Di;

class IndexFactory implements IndexFactoryInterface, ServiceManagerAwareInterface
{
    /**
     *
     * @var ServiceManager
     */
    protected $serviceManager = null;

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
        $index = $dic->get('Index\Index\Index', array(
            'indexName' => $indexName,
            'searchdConfig' => $this->serviceManager->get('SearchdConfig'),
            'indexerConfig' => $this->serviceManager->get('IndexerConfig'),
            'mainIndex' => $this->getMainIndex($indexName),
            'serverId' => SERVER_ID,
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
            'Index\Index\Index',
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
            if (is_array($value) && count($value) == 1 && isset($value['valueFromService'])) {
                $value = $this->getValueFromService($value['valueFromService']);
            } else if (is_array($value)) {
                $this->prepareInstanceConfig($value);
            }
        }
    }

    /**
     * Returns value from specified service
     * Service is an alias from ServiceManager
     *
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    protected function getValueFromService(array $options)
    {
        if (!isset($options['serviceName'])) {
            throw new \Exception('serviceName option must be defined');
        }

        $service = $this->serviceManager->get($options['serviceName']);

        $method = isset($options['serviceMethod']) ? $options['serviceMethod'] : 'get';
        $params = isset($options['parameters']) ? $options['parameters'] : array();
        $value = call_user_func_array(array($service, $method), $params);

        return $value;
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
        $config = $this->serviceManager->get('config');
        if (!isset($config['indexFactory']['indexes'][$configName])) {
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
        $config = $this->serviceManager->get('config');

        $section = $indexerConfig->getSection('index', $indexName);
        if (!$section || !isset($config['indexFactory']['indexes'][$section->getName()])) {
            $section = $indexerConfig->getDistributedFor($indexName);
        }

        if (!$section || !isset($config['indexFactory']['indexes'][$section->getName()])) {
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

        $config = $this->serviceManager->get('config');
        $params = $config['indexFactory']['indexes'][$configName];
        if (isset($params['instanceExtends'])) {
            $params['instance'] = isset($params['instance']) ? $params['instance'] : array();
            $params['instance'] = $this->arrayMergeRecursive(
                $params['instance'],
                $config['indexFactory']['indexes'][$params['instanceExtends']]['instance']
            );
        }

        if (isset($config['indexFactory']['global'])) {
            $params = $this->arrayMergeRecursive(
                $config['indexFactory']['global'],
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