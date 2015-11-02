<?php

namespace SphinxIndex\DataProvider\Plugin;

use SphinxIndex\Entity\Document;

class Rename extends AbstractPlugin
{
    /**
     * Array of fields to rename.
     * array(
     *   'field_name_must_present_in_document' => 'field_name_there_is_in_document',
     *   ...
     * )
     *
     * @var array
     */
    protected $fieldsNames = array();

    /**
     *
     * @param array $fieldsNames
     */
    public function __construct(array $fieldsNames)
    {
        $this->fieldsNames = $fieldsNames;
    }

    /**
     * Renames fields of document
     *
     * @param Document $document
     * @return Document
     */
    public function __invoke(Document $document)
    {
        foreach ($this->fieldsNames as $target => $source) {
            if (!isset($document->{$target})) {
                $document->{$target} = $document->{$source};
                unset($document->{$source});
            }
        }

        return $document;
    }
}
