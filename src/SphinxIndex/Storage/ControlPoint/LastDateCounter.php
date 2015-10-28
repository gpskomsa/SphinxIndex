<?php

namespace SphinxIndex\Storage\ControlPoint;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\TableGateway\TableGateway;

class LastDateCounter implements ControlPointManagerInterface
{
    /**
     *
     * @var TableGateway
     */
    protected $table = null;

    /**
     *
     * @var string
     */
    protected $tableName = 'sphinx_update_last_date';

    /**
     *
     * @var string
     */
    protected $serverId = null;

    /**
     * Index name
     *
     * @var string
     */
    protected $index = null;

    /**
     *
     * @var Adapter
     */
    protected $adapter = null;

    /**
     *
     * @param Adapter $adapter
     * @param array $options
     */
    public function __construct(
        Adapter $adapter,
        array $options = array()
    )
    {
        $this->setAdapter($adapter);
        unset($options['adapter']);
        $this->setOptions($options);
    }

    /**
     *
     * @param Adapter $adapter
     * @return LastDateCounter
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     *
     * @param string $index
     * @return LastDateCounter
     */
    public function setMainIndex($mainIndex)
    {
        $this->index = $mainIndex;

        return $this;
    }

    /**
     *
     * @param string $serverId
     * @return LastDateCounter
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     *
     * @param array $options
     * @return LastDateCounter
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
     *
     * @param string $name
     * @return string
     */
    public function getProperty($name)
    {
        if (!property_exists($this, $name) || null === $this->{$name}) {
            throw new \Exception("Property $name is not defined");
        }

        return $this->{$name};
    }

    /**
     * Returns the last date was used on previous data indexing
     *
     * @return string
     */
    public function getControlPoint()
    {
        $sql = new Sql($this->adapter, $this->getProperty('tableName'));
        $select = $sql->select();
        $where = new Where();

        $select->columns(
            array(
                'last_date' => new Expression('MAX(last_date)')
            )
        );

        $where
            ->equalTo('sphinx_server_id', $this->getProperty('serverId'))
            ->equalTo('index', $this->getProperty('index'));

        $select->where($where, PredicateSet::OP_AND);
        $row = $this->getTable()->selectWith($select)->current();

        return $row['last_date'];
    }

    /**
     * Sets date that is the last date was found while indexing
     *
     * @param string $lastDate
     */
    public function setControlPoint($lastDate)
    {
        $fields = array(
            'sphinx_server_id', 'index', 'last_date'
        );

        $values = array(
            $this->getProperty('serverId'),
            $this->getProperty('index'),
            $lastDate
        );

        $sql = "REPLACE INTO " . $this->tableName
            . " (`" . implode('`,`', $fields) . "`)"
            . " VALUES('" . implode("','", $values) . "')";

        $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
    }

    /**
     *
     * @param TableGateway $table
     * @return LastDateCounter
     */
    public function setTable(TableGateway $table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     *
     * @return TableGateway
     */
    public function getTable()
    {
        if (null === $this->table) {
            $this->table = new TableGateway(
                $this->getProperty('tableName'),
                $this->adapter
            );
        }

        return $this->table;
    }
}