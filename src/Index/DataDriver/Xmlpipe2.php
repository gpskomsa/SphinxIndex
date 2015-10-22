<?php

namespace SphinxIndex\DataDriver;

use Zend\Config;
use Zend\Filter;

use SphinxIndex\DataDriver\DataDriverInterface;
use SphinxIndex\Filter\StripBadXmlUtf8;

class Xmlpipe2 implements DataDriverInterface
{
    /**
     * Fields of index
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Attributes of index
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * All fields and attributes of index
     *
     * @var array
     */
    protected $sections = array();

    /**
     * To escape a string attribute or not from HTML tags
     *
     * @var boolean
     */
    protected $escapeString = false;

    /**
     * Unique field of document that contains the document id
     *
     * @var string
     */
    protected $docIdField = 'id';

    /**
     * Special filters for filtering bad utf8 text from string attributes
     *
     * @var Filter\FilterInterface
     */
    protected $badUtf8Filter = null;

    /**
     *
     * @param array $params
     */
    public function __construct($params)
    {
        if (is_string($params)) {
            $params = Config\Factory::fromFile($params);
        }

        if ($params instanceof Zend\Config\Config) {
            $params = $params->toArray();
        }

        if (!is_array($params)) {
            throw new \Exception('invalid param options type');
        }

        $this->setOptions($params);
    }

    /**
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array($value));
            } elseif(property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Prints the xml-layout of sphinx document into STDOUT
     *
     * @param mixed $documents
     */
    public function addDocuments($documents)
    {
        if (!is_array($documents) && !($documents instanceof \Traversable)) {
            throw new Exception('documents are not traversable using foreach');
        }

        foreach ($documents as $document) {
            if (!isset($document[$this->docIdField])) {
                //throw new \Exception('document id must be set');
                continue;
            }

            echo $this->getDocument($document[$this->docIdField], $document);
        }
    }

    /**
     * Prints the head of xml data of sphinx documents
     */
    public function init()
    {
        $this->sections = array_merge(array_flip($this->fields), $this->attributes);

        echo $this->getHead();
    }

    /**
     * Prints the close part of xml data
     */
    public function finish()
    {
        echo $this->getFoot();
    }

    /**
     * Returns the xml of sphinx:schema
     *
     * @return string
     */
    public function getSchema()
    {
        $buffer = array('<sphinx:schema>');

        foreach ($this->fields as $name) {
            $buffer[] = '<sphinx:field ' . $this->paramsToString($name) . '/>';
        }

        foreach ($this->attributes as $name => $params) {
            $buffer[] = '<sphinx:attr ' . $this->paramsToString($name, $params) . '/>';
        }

        $buffer[] = '</sphinx:schema>';

        return implode("\n", $buffer);
    }

    /**
     * Return the xml of a single document
     *
     * @param integer$id
     * @param mixed $data
     * @return string
     */
    public function getDocument($id, $data)
    {
        $buffer = '<sphinx:document id="' . (integer) $id . '">' . "\n";

        foreach ($this->sections as $name => $params) {
            $value = isset($data[$name]) ? $data[$name] : null;
            if (!isset($params['type']) || 'string' === $params['type']) {
                $value = $this->getBadUtf8Filter()->filter($value);
            }
            if (isset($params['type']) && 'string' === $params['type'] && $this->escapeString) {
                $value = htmlspecialchars($value);
            }

            $buffer .= "<$name><![CDATA[" . $value . "]]></$name>\n";
        }

        $buffer .= "</sphinx:document>\n";

        return $buffer;
    }

    /**
     * Prints the xml of sphinx kill-list
     *
     * @param Traversable $data
     */
    public function removeDocuments($data)
    {
        if (!is_array($data) && !($data instanceof \Traversable)) {
            throw new \Exception('document must be permissible for foreach');
        }

        $ids = array();
        foreach ($data as $document) {
            if (!isset($document[$this->docIdField])) {
                throw new \Exception('document must contain ID field');
            }

            $ids[] = $document[$this->docIdField];
        }

        echo $this->getKilllist($ids);
    }

    /**
     * Returns the xml of kill-list
     *
     * @param array $data
     * @return string
     */
    public function getKilllist(array $data)
    {
        $buffer = "<sphinx:killlist>\n";

        foreach ($data as $value) {
            $value = (integer) $value;
            $buffer .= "<id>$value</id>\n";
        }

        $buffer .= "</sphinx:killlist>\n";

        return $buffer;
    }

    /**
     *
     * @param \Zend\Filter\FilterInterface $filter
     * @return \Index\DataDriver\Xmlpipe2
     */
    public function setBadUtf8Filter(Filter\FilterInterface $filter)
    {
        $this->badUtf8Filter = $filter;

        return $this;
    }

    /**
     *
     * @return \Zend\Filter\FilterInterface
     */
    public function getBadUtf8Filter()
    {
        if (null === $this->badUtf8Filter) {
            $this->setBadUtf8Filter(new StripBadXmlUtf8());
        }

        return $this->badUtf8Filter;
    }

    /**
     *
     * @param array $params
     * @return string
     */
    protected function paramsToString($name, array $attr = array())
    {
        $result = array('name = "' . $name .'"');

        foreach ($attr as $key => $value) {
            $result[] = $key . '="' . $value .'"';
        }

        return implode(' ', $result);
    }

    /**
     *
     * @return string
     */
    public function getHead()
    {
         $buffer = array('<?xml version="1.0" encoding="utf-8"?>');
         $buffer[] = '<sphinx:docset>';
         $buffer[] = $this->getSchema();

         return implode("\n", $buffer);
    }

    /**
     *
     * @return string
     */
    public function getFoot()
    {
        return '</sphinx:docset>';
    }
}