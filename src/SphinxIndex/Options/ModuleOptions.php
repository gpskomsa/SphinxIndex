<?php
namespace SphinxIndex\Options;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions
{

    /**
     * @var string
     */
    protected $schemeStorage = './config/sphinx-config/index/scheme';

    /**
     * @var array
     */
    protected $indexFactoryConfig = array();

    /**
     *
     * @var array
     */
    protected $redisAdapter = array();

    /**
     * set scheme storage
     *
     * @param string $path
     * @return ModuleOptions
     */
    public function setSchemeStorage($path)
    {
        $this->schemeStorage = $path;

        return $this;
    }

    /**
     * get scheme storage
     *
     * @return string
     */
    public function getSchemeStorage()
    {
        return $this->schemeStorage;
    }

    /**
     * set index factory config
     *
     * @param array $config
     * @return \SphinxIndex\Options\ModuleOptions
     */
    public function setIndexFactory(array $config)
    {
        $this->indexFactoryConfig = $config;

        return $this;
    }

    /**
     * get index factory config
     *
     * @return array
     */
    public function getIndexFactory()
    {
        return $this->indexFactoryConfig;
    }

    /**
     *
     * @param array $adapters
     */
    public function setRedisAdapter(array $adapters)
    {
        $this->redisAdapter = $adapters;
    }

    /**
     *
     * @return array
     */
    public function getRedisAdapter()
    {
        return $this->redisAdapter;
    }
}