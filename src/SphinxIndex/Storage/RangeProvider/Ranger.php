<?php

namespace SphinxIndex\Storage\RangeProvider;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

use SphinxConfig\Entity\Config\Section;
use SphinxConfig\Entity\Config\Section\Chunked;
use SphinxIndex\Storage\Chunks;

class Ranger implements RangeProviderInterface
{
    /**
     *
     * @var string
     */
    protected $tableName = 'sphinx_index_range';

    /**
     *
     * @var TableGateway
     */
    protected $table = null;

    /**
     * Sphinx server id
     *
     * @var string
     */
    protected $serverId = null;

    /**
     *
     * @var EventManagerInterface
     */
    protected $events = null;

    /**
     *
     * @var Section
     */
    protected $indexerConfigSection = null;

    /**
     *
     * @param Adapter $adapter
     * @param Section $indexerConfigSection
     * @param string $serverId
     * @param string $tableName
     */
    public function __construct(
        Adapter $adapter,
        Section $indexerConfigSection,
        $serverId,
        $tableName = null)
    {
        $this->indexerConfigSection = $indexerConfigSection;
        $this->serverId = $serverId;

        if ($tableName) {
            $this->tableName = $tableName;
        }

        $this->table = new TableGateway(
            $this->tableName,
            $adapter
        );
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
     * Gets range for current index name and current sphinx server id
     *
     * @param integer $chunkId
     * @return array
     * @throws \RuntimeException
     */
    public function getRange($chunkId)
    {
        $section = $this->indexerConfigSection;
        $index = $section->getName();
        if (!$section instanceof Chunked || !$section->hasChunk($chunkId)) {
            throw new \RuntimeException(
                sprintf(
                    "index %s is not distributed or has not chunk id %d",
                    $index,
                    $chunkId
                )
            );
        }

        $chunkName = $section->getChunk($chunkId)->getName();
        $data = $this->table->select(
            array(
                'index' => $chunkName,
                'sphinx_server_id' => $this->serverId
            )
        )->current();

        if ($data) {
            return array($data['start'], $data['end']);
        }

        return array(0, -1);
    }

    /**
     * Sets index ranges(id ranges) for current distributed index and sphinx server id
     *
     * @param Chunks $ranges
     */
    public function setRange(Chunks $ranges)
    {
        $section = $this->indexerConfigSection;
        $index = $section->getName();
        if (!$section->hasChunks()) {
            throw new \Exception('index ' . $index . ' is not distributed');
        }

        $names = $section->getChunkNames();
        $namesCount = $section->getChunkCount();

        if ($namesCount !== count($ranges)) {
            throw new \Exception(
                'index ' . $index . ' has ' . $namesCount
                . ' chunks but ' . count($ranges) . ' ranges provided'
            );
        }

        foreach ($ranges as $index => $range) {
            $this->table->delete(
                array(
                    'sphinx_server_id' => $this->serverId,
                    'index' => $names[$index]
                    )
            );
            $this->table->insert(array(
                'sphinx_server_id' => $this->serverId,
                'index' => $names[$index],
                'start' => $range[0],
                'end' => $range[1]
            ));
        }

        $this->getEventManager()->trigger(__FUNCTION__, $this, compact('names'));
    }
}
