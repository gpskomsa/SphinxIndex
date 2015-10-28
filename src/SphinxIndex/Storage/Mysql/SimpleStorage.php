<?php

namespace SphinxIndex\Storage\Mysql;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\Storage\ControlPointUsingInterface;
use SphinxIndex\Storage\RangedInterface;
use SphinxIndex\Service\RangeProviderInterface;
use SphinxIndex\Service\ControlPointManagerInterface;
use SphinxIndex\Storage\Mysql\Chunk;
use SphinxIndex\Storage\Stated;

class SimpleStorage implements StorageInterface, ControlPointUsingInterface, RangedInterface
{
    use Stated;

    /**
     *
     * @var Adapter
     */
    protected $adapter = null;

    /**
     *
     * @var string
     */
    protected $tableName = null;

    /**
     *
     * @var type
     */
    protected $docIdField = 'id';

    /**
     *
     * @var ControlPointManagerInterface
     */
    protected $cpManager = null;

    /**
     *
     * @var string
     */
    protected $lastDateField = null;

    /**
     *
     * @var RangeProviderInterface
     */
    protected $ranger = null;

    /**
     *
     * @var EventManagerInterface
     */
    protected $events = null;

    /**
     * Must the last chunk(index) of data be empty
     *
     * @var boolean
     */
    protected $emptyLastRange = true;

    /**
     *
     * @var Chunk\SplitterInterface
     */
    protected $splitter = null;

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
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array($value));
            }
        }
    }

    /**
     *
     * @param Adapter $adapter
     * @return AbstractStorage
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     *
     * @param boolean $value
     * @return AbstractStorage
     */
    public function setEmptyLastRange($value)
    {
        $this->emptyLastRange = (boolean) $value;

        return $this;
    }

    /**
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     *
     * @param EventManagerInterface $events
     * @return Ranger
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
        ));

        $this->events = $events;

        return $this;
    }

    /**
     *
     * @param string $value
     */
    public function setTableName($value)
    {
        $this->tableName = $value;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        if (!property_exists($this, $name) || null === $this->{$name}) {
            throw new \Exception("$name is not defined");
        }

        return $this->{$name};
    }

    /**
     *
     * @param \SphinxIndex\Storage\Mysql\Chunk\SplitterInterface $splitter
     * @return \SphinxIndex\Storage\Mysql\SimpleStorage
     */
    public function setSplitter(Chunk\SplitterInterface $splitter)
    {
        $this->splitter = $splitter;

        return $this;
    }

    /**
     *
     * @return Chunk\SplitterInterface
     */
    public function getSplitter()
    {
        if (null === $this->splitter) {
            $sql = new Sql($this->adapter);
            $select = $sql->select();
            $this->setFrom($select);
            $this->getBaseColumnSelect($select);
            $this->applyConditionsForValidDocs($select);

            $query = new Chunk\Query($this->adapter, $select, $this->getProperty('docIdField'));
            $this->splitter = new Chunk\QuerySplitter($query);
        }

        return $this->splitter;
    }

    /**
     * Sets ranges of data splitting it on several equal chunks
     *
     * @param integer $count
     * @return array
     */
    public function split($count)
    {
        $name = $this->getProperty('tableName');

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, compact('count', 'name'));

        $splitter = $this->getSplitter();

        $result = $splitter->split($count, $this->emptyLastRange);
        $this->ranger->setRange($result);

        $countForChunk = $result->getCountForChunk() ?: $result->getAvgCountForChunkByRanges();
        $emptyLastRange = $this->emptyLastRange;
        $this->getEventManager()->trigger(
            __FUNCTION__ . '.post', $this, compact('count','name','countForChunk','emptyLastRange')
        );
    }

    /**
     * {@inheritdoc}
     * @return ResultSet
     */
    public function getItems()
    {
        if ($this->state(__FUNCTION__)) {
            $this->state(__FUNCTION__, false);
            return false;
        }

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $this->setFrom($select);
        $this->getBaseColumnSelect($select);

        $this->applyConditionsForRange($select);
        $this->applyConditionsForValidDocs($select);
        $this->applyDataJoins($select);

        $rows = $this->adapter->query(
            $sql->getSqlStringForSqlObject($select),
            Adapter::QUERY_MODE_EXECUTE
        );

        $this->state(__FUNCTION__, true);

        return $rows;
    }

    /**
     * @param Select $select
     * @return $this
     */
    protected function applyDataJoins(Select $select)
    {
        return $this;
    }

    /**
     * Apply range condition to Select
     *
     * @param Select $select
     * @return AbstractStorage
     */
    protected function applyConditionsForRange(Select $select)
    {
        $range = $this->ranger ? $this->ranger->getRange() : array(0, -1);

        $custom = new Where();
        $custom->greaterThan('main.' . $this->getProperty('docIdField'), $range[0]);
        if (-1 !== (integer) $range[1]) {
            $custom->lessThanOrEqualTo('main.' . $this->getProperty('docIdField'), $range[1]);
        }

        $select->where->addPredicate($custom, PredicateSet::OP_AND);

        return $this;
    }

    /**
     *
     * @param Select $select
     */
    protected function setFrom($select)
    {
        $select->from(array('main' => $this->getProperty('tableName')));
    }

    /**
     *
     * @param ControlPointManagerInterface $counter
     * @return AbstractStorage
     */
    public function setControlPointManager(ControlPointManagerInterface $controlPointManager)
    {
        $this->cpManager = $controlPointManager;

        return $this;
    }

    /**
     *
     * @param RangeProviderInterface $rangeProvider
     * @return AbstractStorage
     */
    public function setRangeProvider(RangeProviderInterface $rangeProvider)
    {
        $this->ranger = $rangeProvider;

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     * @return ResultSet
     */
    public function getItemsToUpdate()
    {
        if ($this->state(__FUNCTION__)) {
            $this->state(__FUNCTION__, false);
            return false;
        }

        if (null === $this->cpManager
            || !($lastDate = $this->cpManager->getControlPoint())) {
            return false;
        }

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $this->setFrom($select);
        $this->getBaseColumnSelect($select);

        $this->applyConditionsForRange($select);
        $this->applyConditionsForLastDate($select, $lastDate);

        $this->applyConditionsForValidDocs($select);

        $rows = $this->adapter->query(
            $sql->getSqlStringForSqlObject($select),
            Adapter::QUERY_MODE_EXECUTE
        );

        $this->state(__FUNCTION__, true);

        return $rows;
    }

    /**
     * Sets control point that is the last date of indexed data
     */
    public function markControlPoint()
    {
        if (null === $this->cpManager) {
            return;
        }

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $this->setFrom($select);
        $select->columns(
            array(
                $this->getProperty('lastDateField')
            )
        );
        $this->applyConditionsForRange($select);
        $select->order($this->getProperty('lastDateField') . ' DESC');
        $select->limit('1');

        $row = $this->adapter->query(
            $sql->getSqlStringForSqlObject($select),
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        if ($row) {
            $this->cpManager->setControlPoint($row->{$this->getProperty('lastDateField')});
        }
    }

    /**
     * {@inheritdoc}
     * @return ResultSet
     */
    public function getItemsToDelete()
    {
        if ($this->state(__FUNCTION__)) {
            $this->state(__FUNCTION__, false);
            return false;
        }

        if (null === $this->cpManager
            || !($lastDate = $this->cpManager->getControlPoint())) {
            return false;
        }

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $this->setFrom($select);
        $select->columns(
            array(
                $this->getProperty('docIdField')
            )
        );

        $this->applyConditionsForRange($select);
        $this->applyConditionsForLastDate($select, $lastDate);
        $this->applyConditionsForInvalidDocs($select);

        $rows = $this->adapter->query(
            $sql->getSqlStringForSqlObject($select),
            Adapter::QUERY_MODE_EXECUTE
        );

        $this->state(__FUNCTION__, true);

        return $rows;
    }

    /**
     * Applies control point to Select to get only newer data
     *
     * @param Select $select
     * @param string $lastDate
     * @return AbstractMainStorage
     */
    protected function applyConditionsForLastDate(Select $select, $lastDate)
    {
        $custom = new Where();
        $custom->greaterThan('main.' . $this->getProperty('lastDateField'), $lastDate);

        $select->where->addPredicate($custom, PredicateSet::OP_AND);

        return $this;
    }

    /**
     * Applies conditions to Select to point only valid documents
     *
     * @param Select $select
     * @return \SphinxIndex\Storage\Mysql\SimpleStorage
     */
    protected function applyConditionsForValidDocs(Select $select)
    {
        return $this;
    }

    /**
     * Applies conditions to Select to point only invalid documents
     * 
     * @param Select $select
     * @return \SphinxIndex\Storage\Mysql\SimpleStorage
     */
    protected function applyConditionsForInvalidDocs(Select $select)
    {
        return $this;
    }

    /**
     * Sets columns to select for document data
     *
     * @param Select $select
     * @return Select
     */
    protected function getBaseColumnSelect(Select $select)
    {
        $select->columns(array('*'));

        return $select;
    }
}