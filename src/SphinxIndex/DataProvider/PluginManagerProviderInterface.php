<?php

namespace SphinxIndex\DataProvider;

interface PluginManagerProviderInterface
{
    /**
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getDataProviderPluginConfig();
}