<?php

namespace SphinxIndex\DataProvider\Plugin;

use SphinxIndex\DataProvider\DataProviderInterface;

interface PluginInterface
{
    /**
     *
     * @param DataProviderInterface $dataProvider
     */
    public function setDataProvider(DataProviderInterface $dataProvider);

    /**
     *
     * @return DataProviderInterface
     */
    public function getDataProvider();
}
