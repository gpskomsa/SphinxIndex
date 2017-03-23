<?php

namespace SphinxIndex\Index;

use SphinxIndex\DataProvider\DataProviderInterface;
use SphinxIndex\Storage\RangedInterface;
use SphinxIndex\DataDriver\DataDriverInterface;

use SphinxConfig\Entity\Config\Section;

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
     * Section of index in config
     *
     * @var Section
     */
    protected $indexerConfigSection = null;

    /**
     *
     * @var DataProviderInterface
     */
    protected $dataProvider = null;

    /**
     * Driver object
     *
     * @var DataDriverInterface
     */
    protected $dataDriver = null;

    /**
     * @param Section $indexerConfigSection
     * @param DataProviderInterface $dataProvider
     * @param DataDriverInterface $dataDriver
     * @param integer $indexType
     */
    public function __construct(
        Section $indexerConfigSection,
        DataProviderInterface $dataProvider,
        DataDriverInterface $dataDriver,
        $indexType = self::INDEX_TYPE_MAIN
    )
    {
        if ($indexerConfigSection->type === 'distributed') {
            $this->distributed = true;
        }

        $this->indexerConfigSection = $indexerConfigSection;
        $this->dataProvider = $dataProvider;
        $this->setDataDriver($dataDriver);
        $this->type = (integer) $indexType;
    }

    /**
     *
     * @param DataDriverInterface $dataDriver
     * @return \SphinxIndex\Index\Index
     */
    public function setDataDriver(DataDriverInterface $dataDriver)
    {
        $this->dataDriver = $dataDriver;

        return $this;
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
     * @param integer|null $chunkId
     * @return void
     */
    public function build($chunkId = null)
    {
        if (null === $chunkId && $this->distributed) {
            return;
        }

        $this->dataDriver->init();

        if ($this->isDelta()) {
            while ($documents = $this->dataProvider->getDocumentsToUpdate($chunkId)) {
                $this->dataDriver->addDocuments($documents);
            }

            while ($documentsToDelete = $this->dataProvider->getDocumentsToDelete($chunkId)) {
                $this->dataDriver->removeDocuments($documentsToDelete);
            }
        } else {
            while ($documents = $this->dataProvider->getDocumentsToInsert($chunkId)) {
                $this->dataDriver->addDocuments($documents);
            }
        }

        $this->dataDriver->finish();
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

        $storage->split($this->indexerConfigSection->getChunkCount());
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