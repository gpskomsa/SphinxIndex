<?php

namespace SphinxIndex\Controller;

use Zend\Mvc\Controller\AbstractActionController;

use SphinxIndex\Index\IndexFactoryInterface;

class IndexController extends AbstractActionController
{
    /**
     *
     * @var IndexFactoryInterface
     */
    protected $indexFactory = null;

    /**
     *
     * @param IndexFactoryInterface $factory
     */
    public function __construct(IndexFactoryInterface $factory)
    {
        $this->indexFactory = $factory;
    }

    /**
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $indexName = (string) $this->params()->fromRoute('index');
        $command = $this->params()->fromRoute('command');
        $index = $this->indexFactory->getIndex($indexName);
        $index->{$command}();
    }
}
