<?php

namespace SphinxIndex\Index;

class IndexManagerProviderInterface
{
    /**
     * @return array|\Zend\ServiceManager\Config
     */
    public function getSphinxIndexManagerConfig();
}