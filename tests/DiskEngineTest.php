<?php
namespace Tests;

use Mockery;
use Laravel\Scout\Builder;
use UniSharp\LaravelScoutDiskDriver\ScoutDiskEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\SearchableTestModel as Model;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class DiskEngineTest extends \PHPUnit\Framework\TestCase
{

    private $file;

    public function setUp()
    {
        vfsStream::newFile('foo.txt')->at(vfsStream::setup('root'));
        $this->file = vfsStream::url('root/foo.txt');
        parent::setUp();
    }

    public function xtestUpdateIndex()
    {
        $data = ['id' => 1, 'message' => 'Hello World'];
        $entity = new Model($data);
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $engine->update(Collection::make([$entity]));
    }

    public function testSearchWithinSingleItemAndFound()
    {
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        // single item, single match
        $data = ['id' => 1, 'message' => 'Hello World'];
        $entity = new Model($data);
        $engine->update(Collection::make([$entity]));
        $builder = new Builder($entity, 'llo');
        $this->assertEquals(Collection::make([$entity])->toArray(), $engine->search($builder)->toArray());
    }

    public function testSearchWithinMultipleItemsAndFound()
    {
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        // multie item, single match
        $data = ['id' => 1, 'message' => 'Hello World'];
        $entity1 = new Model($data);
        $data = ['id' => 2, 'message' => 'Hell World'];
        $entity2 = new Model($data);

        $engine->update(Collection::make([$entity1, $entity2]));

        $builder = new Builder($entity1, 'llo');
        $this->assertEquals(Collection::make([$entity1]), $engine->search($builder));
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
