<?php

namespace SphinxIndex\DataDriver;

use SphinxIndex\DataDriver\DataDriverInterface;
use SphinxConfig\Entity\Config;

use SphinxIndex\Entity\DocumentSet;

class RT implements DataDriverInterface
{
    /**
     * Название индекса с которым работает драйвер
     *
     * @var string
     */
    protected $index = null;

    /**
     * Sphinx server configuration
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Index fields
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
     * Common list of fields and attributes
     *
     * @var array
     */
    protected $keys = array();

    /**
     *
     * @param Config $searchdConfig
     * @param tring $indexName
     */
    public function __construct(
        Config $searchdConfig,
        $indexName)
    {
        $this->config = $searchdConfig;
        $this->index = (string) $indexName;

        $this->setScheme();
    }

    /**
     * Determines fields and attribute set from the index config
     */
    protected function setScheme()
    {
        $section = $this->config->getSection('index', $this->index);
        if (!$section) {
            throw new \Exception('index section `' . $this->index . '` not found in config');
        }

        $this->fields = (array) $section->rt_field;
        $this->attributes = array(
            'uint' => (array) $section->rt_attr_uint,
            'bigint' => (array) $section->rt_attr_bigint,
            'float' => (array) $section->rt_attr_float,
            'multi' => (array) $section->rt_attr_multi,
            'multi_64' => (array) $section->rt_attr_multi_64,
            'timestamp' => (array) $section->rt_attr_timestamp,
            'string' => (array) $section->rt_attr_string,
        );

        $keys = array('id' => 'uint');
        foreach ($this->fields as $field) {
            $keys[$field] = 'string';
        }

        foreach ($this->attributes as $type => $attrs) {
            foreach ($attrs as $attr) {
                if (!isset($keys[$attr])) {
                    $keys[$attr] = $type;
                }
            }
        }

        $this->keys = $keys;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function addDocuments(DocumentSet $documents)
    {
        $documentFieldsTxt = implode(',', array_keys($this->keys));
        foreach ($documents as $document) {
            $documentValues = array();
            foreach ($this->keys as $key => $type) {
                if (isset($document->{$key})) {
                    $value = $document->{$key};
                    if ('string' === $type) {
                        $value = "'" . str_replace(
                            array('\\', '\''),
                            array('\\\\', '\\\''),
                            $value
                        ) . "'";
                    }

                    $documentValues[] = $value;
                } else {
                    switch ($type) {
                        case 'uint':
                        case 'bigint':
                        case 'float':
                            $documentValues[] = 0;
                            break;
                        default:
                            $documentValues[] = '';
                            break;
                    }
                }
            }

            $sql = sprintf(
                "REPLACE INTO %s (%s) VALUES (%s);\n",
                $this->index,
                $documentFieldsTxt,
                implode(',', $documentValues)
            );

            echo $sql;
        }
    }

    /**
     *
     * {@inheritdoc}
     * @todo need to be implemented
     */
    public function removeDocuments(DocumentSet $documents)
    {
        throw new \Exception('not emplemented yet');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function init()
    {}

    /**
     *
     * {@inheritdoc}
     */
    public function finish()
    {}
}
