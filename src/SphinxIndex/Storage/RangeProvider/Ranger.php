<?php

namespace SphinxIndex\Storage\RangeProvider;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

use SphinxIndex\Storage\Chunks;

class Ranger implements RangeProviderInterface, ServiceManagerAwareInterface
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
     * Index name
     *
     * @var string
     */
    protected $index = null;

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
     * @var ServiceManager
     */
    protected $serviceManager = null;

    /**
     *
     * @param Adapter $adapter
     * @param string $tableName
     */
    public function __construct(
        Adapter $adapter,
        $tableName = null)
    {
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
     * @param ServiceManager $serviceManager
     * @return Ranger
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

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
     * Sets the index name for object
     *
     * @param type $mainIndex
     * @return Ranger
     */
    public function setMainIndex($mainIndex)
    {
        $this->index = $mainIndex;

        return $this;
    }

    /**
     * Sets the sphinx server id
     *
     * @param string $serverId
     * @return Ranger
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     * Gets range for current index name and current sphinx server id
     *
     */
    public function getRange()
    {
        if (null === $this->index) {
            throw new \Exception('index must be set');
        }

        $data = $this->table->select(
            array(
                'index' => $this->index,
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
    public function setRange(
        Chunks $ranges
    )
    {
        $config = $this->serviceManager->get('IndexerConfig');
        $section = $config->getSection('index', $this->index);
        if (!$section->hasChunks()) {
            throw new \Exception('index ' . $this->index . ' is not distributed');
        }

        $names = $section->getChunkNames();
        $namesCount = $section->getChunkCount();

        if ($namesCount !== count($ranges)) {
            throw new \Exception(
                'index ' . $this->index . ' has ' . $namesCount
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
