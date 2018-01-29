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

    public function testUpdateIndex()
    {
        $data = ['id' => 1, 'message' => 'Hello World'];
        $entity = new Model($data);
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $this->assertTrue($engine->update(Collection::make([$entity])));
    }

    public function testFullTextSearchWithinSingleItemFound()
    {
        $searchTerm = 'llo';
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $input = [
            new Model(['id' => 1, 'message' => 'Hello']),
            new Model(['id' => 2, 'message' => 'ok'])
        ];
        $engine->update(Collection::make($input));
        $builder = new Builder(new Model(), $searchTerm);
        $this->assertEquals(Collection::make([$input[0]]), $engine->search($builder));
    }

    public function testFullTextSearchWithinSingleItemFound2()
    {
        $searchTerm = 'llo';
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $input = [
            new Model(['id' => 1, 'message' => 'Hello']),
            new Model(['id' => 2, 'message' => 'ooooooook'])
        ];
        $engine->update(Collection::make($input));
        $builder = new Builder(new Model(), $searchTerm);
        $this->assertEquals(Collection::make([$input[0]]), $engine->search($builder));
    }

    public function testFullTextSearchWithinMultipleItemsAndFoundWithCorrectRank()
    {
        $searchTerm = 'llo';
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $input = [
            new Model(['id' => 1, 'message' => 'Hell World']),
            new Model(['id' => 2, 'message' => 'Hello World']),
            new Model(['id' => 3, 'message' => 'Joy']),
        ];

        $expected = [
            new Model(['id' => 2, 'message' => 'Hello World']),
            new Model(['id' => 1, 'message' => 'Hell World']),
        ];

        $engine->update(Collection::make($input));
        $builder = new Builder(new Model(), $searchTerm);
        $this->assertEquals(Collection::make($expected), $engine->search($builder));
    }

    public function testFullTextSearchFromLargeFileWithSingleItemFound()
    {
        $searchTerm = 'foo';
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);
        $collection = new Collection();
        for ($i = 1; $i < 1000; $i++) {
            $data = ['id' => $i, 'message' => 'Hello World'];
            $collection->push(new Model($data));
        }

        $timeIndexingStart = microtime(true);
        $engine->update($collection);
        // fwrite(STDERR, "Index: " . (microtime(true) - $timeIndexingStart) . "\n");


        $data = ['id' => $i, 'message' => 'Hello foo World'];
        $entity = new Model($data);
        $engine->update(Collection::make([$entity]));
        $builder = new Builder($entity, $searchTerm);

        $timeSearchingStart = microtime(true);
        $this->assertEquals(Collection::make([$entity]), $engine->search($builder));
        // fwrite(STDERR, "Search: " . (microtime(true) - $timeSearchingStart) . "\n");
    }

    public function testSearchRanking()
    {
        $engine = new ScoutDiskEngine();
        $engine->setStoragePath($this->file);

        $data[0] = ['id' => 1, 'message' => 'A cat is running'];
        $data[1] = ['id' => 2, 'message' => 'A dog is running'];
        $data[2] = ['id' => 3, 'message' => 'A car tool'];

        $searchTerm = 'cat';

        $expectedResultWithRanking[0] = new Model($data[0]); // cat
        $expectedResultWithRanking[1] = new Model($data[2]); // car


        $inputCollection = new Collection();
        foreach ($data as $item) {
            $inputCollection->push(new Model($item));
        }

        $engine->update($inputCollection);

        $builder = new Builder(new Model(), $searchTerm);
        $this->assertEquals(Collection::make($expectedResultWithRanking), $engine->search($builder));
    }

    // public function testSearchRankingAgain()
    // {
    //     $engine = new ScoutDiskEngine();
    //     $engine->setStoragePath($this->file);

    //     $data[0] = ['id' => 1, 'message' => 'A cat is running'];
    //     $data[1] = ['id' => 2, 'message' => 'A dog is running'];
    //     $data[2] = ['id' => 3, 'message' => 'A car tool'];

    //     $searchTerm = 'car';

    //     $expectedResultWithRanking[0] = new Model($data[2]); // car
    //     $expectedResultWithRanking[1] = new Model($data[0]); // cat


    //     $inputCollection = new Collection();
    //     foreach ($data as $item) {
    //         $inputCollection->push(new Model($item));
    //     }

    //     $engine->update($inputCollection);

    //     $builder = new Builder(new Model(), $searchTerm);
    //     $this->assertEquals(Collection::make($expectedResultWithRanking), $engine->search($builder));
    // }

    public function tearDown()
    {
        parent::tearDown();
    }
}
