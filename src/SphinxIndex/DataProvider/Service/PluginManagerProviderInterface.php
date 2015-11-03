<?php

namespace SphinxIndex\DataProvider\Service;

interface PluginManagerProviderInterface
{
    /**
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getDataProviderPluginConfig();
}