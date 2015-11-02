<?php

namespace SphinxIndex\DataProvider\Plugin;

use Zend\Filter\FilterInterface;

use SphinxIndex\Entity\Document;

class Filters extends AbstractPlugin
{
    /**
     * Filters data.
     * array(
     *   'field/attribute of document' => array(
     *     'class or object of filter',
     *     'class or object of filter',
     *     ...
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
     * @return array
     */
    public function getFieldFilters()
    {
        return $this->fieldFilters;
    }

    /**
     *
     * @param Document $document
     * @return Filters|Document
     */
    public function __invoke(Document $document = null)
    {
        if (null === $document) {
            return $this;
        }

        return $this->filter($document);
    }

    /**
     * Filters fields and attributes of document
     *
     * @param Document $document
     * @return Document
     */
    public function filter(Document $document)
    {
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

        return $document;
    }
}