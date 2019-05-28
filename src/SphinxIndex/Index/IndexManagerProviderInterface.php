<?php

namespace SphinxIndex\Index;

interface IndexManagerProviderInterface
{
    /**
     * @return array|\Zend\ServiceManager\Config
     */
    public function getSphinxIndexManagerConfig();
}