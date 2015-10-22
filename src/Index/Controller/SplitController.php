<?php

namespace SphinxIndex\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use SphinxConfig\Service\ConfigFactoryInterface;
use SphinxIndex\Index\IndexFactoryInterface;

class SplitController extends AbstractActionController
{
    /**
     *
     * @var IndexFactoryInterface
     */
    protected $indexFactory = null;

    /**
     *
     * @param ConfigFactoryInterface $factory
     * @param IndexFactoryInterface $factory
     */
    public function __construct(IndexFactoryInterface $iFactory)
    {
        $this->indexFactory = $iFactory;
    }

    /**
     *
     * @return ViewModel
     */
    public function splitAction()
    {
        $indexName = (string) $this->params()->fromRoute('index');
        $index = $this->indexFactory->getIndex($indexName);
        if (!$index->isDistributed()) {
            throw new \Exception("index '$indexName' not distributed");
        }

        $index->split();
    }
}