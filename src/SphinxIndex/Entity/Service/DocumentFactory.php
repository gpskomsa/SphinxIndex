<?php

namespace SphinxIndex\Entity\Service;

use SphinxIndex\Entity\Document;

class DocumentFactory
{
    /**
     * Prototype of created entity
     *
     * @var Document
     */
    protected $entityProto = null;

    /**
     *
     * @param Document $entityProto
     */
    public function __construct(Document $entityProto = null)
    {
        if (null !== $entityProto) {
            $this->setEntityProto($entityProto);
        }
    }

    /**
     * Creates new entity of Document
     *
     * @param mixed $data
     * @return Document
     * @throws \Exception
     */
    public function create($data)
    {
        if ($data instanceof Document) {
            $data = $data->getValues();
        } else if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            throw new \Exception('cannot create document');
        }

        $entity = clone $this->getEntityProto();
        $entity->exchangeArray($data);

        return $entity;
    }

    /**
     *
     * @return Document
     */
    public function getEntityProto()
    {
        if (null === $this->entityProto) {
            $this->setEntityProto(new Document);
        }

        return $this->entityProto;
    }

    /**
     *
     * @param Document $entityProto
     * @return DocumentFactory
     */
    public function setEntityProto(Document $entityProto)
    {
        $this->entityProto = $entityProto;

        return $this;
    }
}