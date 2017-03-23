<?php

namespace SphinxIndex\DataProvider\Plugin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventInterface;

use SphinxIndex\DataProvider\DataProvider;

class Rename extends AbstractPlugin implements ListenerAggregateInterface
{
    /**
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Array of fields to rename.
     * array(
     *   'field_name_must_present_in_document' => 'field_name_there_is_in_document',
     *   ...
     * )
     *
     * @var array
     */
    protected $fieldsNames = array();

    /**
     *
     * @param array $fieldsNames
     */
    public function __construct(array $fieldsNames)
    {
        $this->fieldsNames = $fieldsNames;
    }

    /**
     *
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            array(
                DataProvider::EVENT_DOCUMENTS_TO_INSERT,
                DataProvider::EVENT_DOCUMENTS_TO_UPDATE
            ),
            array($this, 'rename')
        );
    }

    /**
     *
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $key => $handler) {
            $events->detach($handler);
            unset($this->listeners[$key]);
        }

        $this->listeners = array();
    }

    /**
     * Renames fields of document
     *
     * @param EventInterface $e
     * @return EventInterface
     */
    public function rename(EventInterface $e)
    {
        if (empty($this->fieldsNames)) {
            return $e;
        }

        $documents = $e->getParam('documents');
        foreach ($documents as $document) {
            foreach ($this->fieldsNames as $target => $source) {
                if (!isset($document->{$target})) {
                    $document->{$target} = $document->{$source};
                    unset($document->{$source});
                }
            }
        }

        return $e;
    }
}
