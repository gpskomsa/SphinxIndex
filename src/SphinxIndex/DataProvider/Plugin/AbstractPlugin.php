<?php

namespace SphinxIndex\DataProvider\Plugin;

use SphinxIndex\DataProvider\DataProviderInterface;

abstract class AbstractPlugin implements PluginInterface
{
    /**
     * Instance of DataProviderInterface that ownes this plugin
     *
     * @var DataProviderInterface
     */
    protected $dataProvider = null;

    /**
     *
     * @param DataProviderInterface $dataProvider
     * @return AbstractPlugin
     */
    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     *
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        if (null === $this->dataProvider) {
            throw new \Exception('DataProvider is not set');
        }

        return $this->dataProvider;
    }
}
