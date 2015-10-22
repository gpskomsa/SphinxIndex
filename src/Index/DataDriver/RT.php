<?php

namespace SphinxIndex\DataDriver;

use SphinxIndex\DataDriver\DataDriverInterface;
use SphinxConfig\Entity\Config;

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
     * Determines fields and attributes set from the index config
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

        $keys = array_merge(array('id'), $this->fields);
        foreach ($this->attributes as $attrs) {
            if (!empty($attrs)) {
                $keys = array_merge($keys, $attrs);
            }
        }

        $this->keys = array_unique($keys);
    }

    /**
     *
     * {@inheritdoc}
     * @todo need to be implemented
     */
    public function addDocuments($documents)
    {
        throw new \Exception('not emplemented yet');
    }

    /**
     *
     * {@inheritdoc}
     * @todo need to be implemented
     */
    public function removeDocuments($documents)
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
