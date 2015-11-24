<?php

namespace SphinxIndex\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SplitController extends AbstractActionController
{
    /**
     *
     * @return ViewModel
     */
    public function splitAction()
    {
        $indexName = (string) $this->params()->fromRoute('index');
        $index = $this->getServiceLocator()
                ->get('SphinxIndex\Index\IndexManager')
                ->get($indexName);

        if (!$index->isDistributed()) {
            throw new \Exception("index '$indexName' not distributed");
        }

        $index->split();
    }
}