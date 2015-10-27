<?php

namespace SphinxIndex\Storage\Mysql\Chunk;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Adapter\Adapter;

class Query implements QueryInterface
{
    /**
     * Base select
     *
     * @var Select
     */
    protected $select = null;

    /**
     *
     * @var string
     */
    protected $keyField = null;

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
        Select $select,
        $keyField)
    {
        $this->adapter = $adapter;

        $this->select = $select;

        $this->keyField = (string) $keyField;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        $select = clone $this->select;
        $select
            ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
            ->limit(1);

        $sql = new Sql($this->adapter);
        $this->adapter
            ->query($sql->getSqlStringForSqlObject($select), Adapter::QUERY_MODE_EXECUTE);

        $row = $this->adapter
            ->query('SELECT FOUND_ROWS() as total_count', Adapter::QUERY_MODE_EXECUTE)
            ->current();

        return $row['total_count'];
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getMaxIdForChunk($countForChunk, $minId)
    {
        $select = clone $this->select;

        $table = $select->getRawState(Select::TABLE);
        if (is_array($table)) {
            $table = key($table);
        }

        $columns = $select->getRawState(Select::COLUMNS);
        if (!in_array($this->keyField, $columns)) {
            $columns[] = $this->keyField;
            $select->columns($columns);
        }
        $select->where(
            array(
                $this->adapter->getPlatform()->quoteIdentifier($table)
                . "."
                . $this->adapter->getPlatform()->quoteIdentifier($this->keyField) . ' > ?' => $minId
            )
        );
        $select->order("$table." . $this->keyField . ' ASC');
        $select->limit(1);
        $select->offset($countForChunk - 1);

        $sql = new Sql($this->adapter);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row = $statement->execute()->current();

        if ($row) {
            return $row[$this->keyField];
        }

        return -1;
    }
}