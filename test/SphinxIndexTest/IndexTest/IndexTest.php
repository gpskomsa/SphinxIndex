<?php

declare(strict_types=1);

namespace SphinxIndexTest\IndexTest;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

use SphinxConfig\Entity\Config\Section\Index as IndexSection;
use SphinxConfig\Config\Reader\Yaml;

use SphinxIndex\Index\Index;
use SphinxIndex\Entity\DocumentSet;
use SphinxIndex\DataProvider\DataProvider;
use SphinxIndex\DataDriver\Xmlpipe2;
use SphinxIndex\Storage\StorageInterface;

class IndexTest extends TestCase
{
    protected $index = null;

    public function setUp()
    {
        \Zend\Config\Factory::registerReader('yaml', new Yaml(
                array('Symfony\Component\Yaml\Yaml', 'parse')
            )
        );

        $indexerConfigSection = $this->createMock(IndexSection::class);
        $indexerConfigSection->type = null;

        $documentSet = new DocumentSet();
        $documentSet->set(
            [
                ['id' => 1, 'title' => 'Title1', 'description' => 'Description1', 'duration' => 60, 'created' => 1559000462],
                ['id' => 2, 'title' => 'Title2', 'description' => 'Description2', 'duration' => 120, 'created' => 1559000462],
            ]
        );

        $storage = $this->prophesize(StorageInterface::class);
        $storage->getItems(Argument::exact(null))->willReturn($documentSet, new DocumentSet());
        $storage->getItemsToDelete(Argument::exact(null))->willReturn(new DocumentSet());
        $storage->getItemsToUpdate(Argument::exact(null))->willReturn(new DocumentSet());

        $provider = new DataProvider($storage->reveal());
        $driver = new Xmlpipe2('./example/config/scheme/video.yaml');
        $driver->setUseBuffer(true);

        $this->index = new Index(
            $indexerConfigSection,
            $provider,
            $driver,
            Index::INDEX_TYPE_MAIN
        );
    }

    public function testBuild()
    {
        $this->index->build();

        $buffer = $this->index->getDataDriver()->getBuffer();
        $this->assertIsString($buffer);

        $this->assertRegExp('/^\<\?xml version="1\.0" encoding="utf-8"\?\>/i', $buffer);
        $this->assertRegExp('/^\<sphinx\:docset\>/im', $buffer);
        $this->assertRegExp('/^\<\/sphinx\:docset\>/im', $buffer);
        $this->assertRegExp('/^\<sphinx\:schema\>/im', $buffer);
        $this->assertRegExp('/^\<\/sphinx\:schema\>/im', $buffer);

        $this->assertRegExp('/\<sphinx\:document id="1"\>/im', $buffer);
        $this->assertRegExp('/\<sphinx\:document id="2"\>/im', $buffer);

        $parser = xml_parser_create();
        $this->assertSame(1, xml_parse($parser, $buffer, true));
    }
}