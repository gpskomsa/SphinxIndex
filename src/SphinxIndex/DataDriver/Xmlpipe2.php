<?php

namespace SphinxIndex\DataDriver;

use Zend\Config;
use Zend\Filter;

use SphinxIndex\DataDriver\DataDriverInterface;
use SphinxIndex\Filter\StripBadXmlUtf8;
use SphinxIndex\Entity\DocumentSet;
use SphinxIndex\Entity\Document;

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
     * Special filters for filtering bad utf8 text from string attributes
     *
     * @var Filter\FilterInterface
     */
    protected $badUtf8Filter = null;

    /**
     * @param string $indexConfig
     * @param array $options
     */
    public function __construct($indexConfig, array $options = array())
    {
        $this->parse($indexConfig);
        $this->setOptions($options);
    }

    /**
     *
     * @param string|array $indexConfig
     * @return Xmlpipe2
     * @throws \Exception
     */
    protected function parse($indexConfig)
    {
        if (is_string($indexConfig)) {
            $data = Config\Factory::fromFile($indexConfig);
        } else if (is_array($indexConfig)) {
            $data = $indexConfig;
        } else {
            throw new \Exception('Invalid type of indexConfig');
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'fields':
                    $this->fields = $value;
                    break;
                case 'attributes':
                    $this->attributes = $value;
                    break;
                default:
                    throw new \Exception('unknown option ' . $key);
                    break;
            }
        }

        return $this;
    }

    /**
     *
     * @param array $options
     * @return Xmlpipe2
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array($value));
            }
        }

        return $this;
    }

    /**
     * Prints the xml-layout of sphinx document into STDOUT
     *
     * @param DocumentSet $documents
     */
    public function addDocuments(DocumentSet $documents)
    {
        $this->initHead();
        foreach ($documents as $document) {
            if (!$document->getKeyValue()) {
                continue;
            }

            echo $this->getDocumentXML($document->getKeyValue(), $document);
        }
    }

    /**
     * Prints the head of xml data of sphinx documents
     */
    public function init()
    {
        $this->sections = array_merge(array_flip($this->fields), $this->attributes);
    }

    /**
     * Output head if needed
     *
     * @return Xmlpipe2
     */
    protected function initHead()
    {
        $buffer = array('<?xml version="1.0" encoding="utf-8"?>');
        $buffer[] = '<sphinx:docset>';
        $buffer[] = '<sphinx:schema>';

        foreach ($this->fields as $name) {
            $buffer[] = '<sphinx:field ' . $this->paramsToString($name) . '/>';
        }

        foreach ($this->attributes as $name => $params) {
            $buffer[] = '<sphinx:attr ' . $this->paramsToString($name, $params) . '/>';
        }

        $buffer[] = '</sphinx:schema>';

        echo implode("\n", $buffer);

        return $this;
    }

    /**
     * Prints the close part of xml data
     */
    public function finish()
    {
        echo $this->getFoot();
    }

    /**
     * Return the xml of a single document
     *
     * @param integer $id
     * @param Document $document
     * @return string
     */
    public function getDocumentXML($id, Document $document)
    {
        $buffer = '<sphinx:document id="' . (integer) $id . '">' . "\n";

        foreach ($this->sections as $name => $params) {
            $value = isset($document->{$name}) ? $document->{$name} : null;
            if (!isset($params['type']) || 'string' === $params['type']) {
                $value = $this->getBadUtf8Filter()->filter($value);
            }

            $buffer .= "<$name><![CDATA[" . $value . "]]></$name>\n";
        }

        $buffer .= "</sphinx:document>\n";

        return $buffer;
    }

    /**
     * Prints the xml of sphinx kill-list
     *
     * @param DocumentSet $documents
     */
    public function removeDocuments(DocumentSet $documents)
    {
        $this->initHead();
        $ids = array();
        foreach ($documents as $document) {
            if (!$document->getKeyValue()) {
                continue;
            }

            $ids[] = $document->getKeyValue();
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
    public function getFoot()
    {
        return '</sphinx:docset>';
    }
}