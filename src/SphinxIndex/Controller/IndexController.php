<?php

namespace SphinxIndex\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    /**
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $indexName = (string) $this->params()->fromRoute('index');
        $command = $this->params()->fromRoute('command');
        $index = $this->getServiceLocator()->get("SphinxIndex\\Index\\Index\\" . $indexName);
        $index->{$command}();
    }
}
