<?php

namespace SphinxIndex\DataProvider\Plugin;

use Zend\Filter\FilterInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventInterface;

use SphinxIndex\DataProvider\DataProvider;

class Filters extends AbstractPlugin implements ListenerAggregateInterface
{
    /**
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Filters data.
     * array(
     *   'field/attribute of result document' => array(
     *     'field' => 'name of source field',
     *     'filters' => array(
     *       'class or object of filter',
     *       'class or object of filter',
     *       ...
     *     ),
     *   ),
     *   ...
     * )
     *
     * @var array
     */
    protected $fieldFilters = array();

    /**
     *
     * @param array $fieldFilters
     */
    public function __construct(array $fieldFilters = array())
    {
        $this->setFieldFilters($fieldFilters);
    }

    /**
     * Sets the filters
     *
     * @param array $fieldFilters
     * @throws \Exception
     */
    public function setFieldFilters(array $fieldFilters)
    {
        $this->fieldFilters = $fieldFilters;

        foreach ($this->fieldFilters as &$data) {
            if (!is_array($data['filters'])) {
                $data['filters'] = array($data['filters']);
            }

            foreach ($data['filters'] as &$filter) {
                if (is_array($filter)) {
                    $name = array_shift($filter);

                    $ref = new \ReflectionClass($name);
                    $filter = $ref->newInstanceArgs($filter);
                } elseif (is_string($filter)) {
                    $filter = new $filter;
                }

                if (!is_object($filter)) {
                    throw new \Exception('invalid type of filter');
                }

                if (!$filter instanceof FilterInterface) {
                    throw new \Exception('filter must implement FilterInterface');
                }
            }
        }
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
            array($this, 'filter')
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
     * Filters fields and attributes of document
     *
     * @param EventInterface $e
     * @return EventInterface
     */
    public function filter(EventInterface $e)
    {
        $document = $e->getParam('document');
        foreach ($this->fieldFilters as $field => $data) {
            if (!isset($document->{$data['field']})) {
                $document->{$data['field']} = null;
            }

            $value = $document->{$data['field']};
            foreach ($data['filters'] as $filter) {
                $value = $filter->filter($value);
            }

            $document->{$field} = $value;
        }

        return $e;
    }
}