<?php

namespace SphinxIndex\Storage\Mysql\Chunk;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

use SphinxIndex\Storage\Chunks;

class SimpleSplitter implements SplitterInterface
{
    /**
     *
     * @var string
     */
    protected $table = null;

    /**
     *
     * @var string
     */
    protected $key = null;

    /**
     *
     * @var Adapter
     */
    protected $adapter = null;

    /**
     *
     * @param Adapter $adapter
     * @param string $table
     * @param string $key
     */
    public function __construct(Adapter $adapter, $table, $key = 'id')
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->key = $key;
    }

    /**
     *
     * @return integer
     */
    public function getLastId()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->from($this->table);
        $select->columns(array('maxId' => new Expression("MAX(" . $this->key . ")")));

        $statement = $sql->prepareStatementForSqlObject($select);
        $row = $statement->execute()->current();

        if ($row) {
            return $row['maxId'];
        }

        return 0;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function split($chunkCount, $lastIsEmpty)
    {
        $maxId = $this->getLastId();
        $chunks = new Chunks();

        if(!$maxId) {
            return $chunks;
        }

        $chunks->reset();
        if ($chunkCount == 1) {
            $chunks[] = array(0, -1);
        } else {
            $start = 0;
            $realCount = $lastIsEmpty ? ($chunkCount - 1) : $chunkCount;
            $countForChunk = (integer) floor($maxId / $realCount);
            for ($i = 0; $i < $realCount; $i++) {
                $chunks[] = array($start, $start + $countForChunk);
                $start += $countForChunk;
            }

            if ($lastIsEmpty) {
                $chunks[] = array($start, -1);
            }
        }

        return $chunks;
    }
}
