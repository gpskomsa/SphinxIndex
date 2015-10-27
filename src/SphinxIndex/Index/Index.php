<?php

namespace SphinxIndex\Index;

use SphinxIndex\DataProvider\DataProviderInterface;
use SphinxIndex\Storage\RangedInterface;

use SphinxConfig\Entity\Config;

class Index
{
    const INDEX_TYPE_MAIN = 1;
    const INDEX_TYPE_DELTA = 2;

    protected $type = null;

    /**
     * Is index distributed
     *
     * @var boolean
     */
    protected $distributed = false;

    /**
     * Config of indexer tool
     *
     * @var Config
     */
    protected $indexerConfig = null;

    /**
     * Index name
     *
     * @var string
     */
    protected $indexName = null;

    /**
     *
     * @var DataProviderInterface
     */
    protected $dataProvider = null;

    /**
     * @param string $indexName
     * @param Config $indexerConfig
     * @param DataProviderInterface $dataProvider
     * @param integer $indexType
     */
    public function __construct(
        $indexName,
        Config $indexerConfig,
        DataProviderInterface $dataProvider,
        $indexType = self::INDEX_TYPE_MAIN
    )
    {
        $this->indexName = $indexName;
        $section = $indexerConfig->getSection('index', $this->indexName);
        if ($section && $section->type === 'distributed') {
            $this->distributed = true;
        }

        $this->indexerConfig = $indexerConfig;
        $this->dataProvider = $dataProvider;
        $this->type = (integer) $indexType;
    }

    /**
     *
     * @return boolean
     */
    public function isDistributed()
    {
        return $this->distributed;
    }

    /**
     * Is index 'delta'?
     *
     * @return integer
     */
    public function isDelta()
    {
        return ($this->type === self::INDEX_TYPE_DELTA);
    }

    /**
     *
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * Builds index data
     *
     * @return void
     */
    public function build()
    {
        if ($this->distributed) {
            return;
        }

        if ($this->isDelta()) {
            $this->dataProvider->updateDocuments();
        } else {
            $this->dataProvider->setDocuments();
        }
    }

    /**
     * Split index if it is distributed
     *
     * @return void
     * @throws \Exception
     */
    public function split()
    {
        if (!$this->distributed || $this->isDelta()) {
            return;
        }

        $storage = $this->dataProvider->getStorage();

        if (!$storage instanceof RangedInterface) {
            throw new \Exception('storage must implement RangedInterface');
        }

        $section = $this->indexerConfig->getSection('index', $this->indexName);
        $storage->split($section->getChunkCount());
    }

    /**
     * Builds index data for update
     *
     * @return void
     */
    public function update()
    {
        if ($this->distributed) {
            return;
        }

        $this->dataProvider->updateDocuments();
    }
}