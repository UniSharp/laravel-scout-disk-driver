<?php

namespace UniSharp\LaravelScoutDiskDriver;

use Laravel\Scout\Builder;
use Illuminate\Database\Eloquent\Collection;

class ScoutDiskEngine extends \Laravel\Scout\Engines\Engine
{

    private $path = '/tmp/ScoutDiskEngineFakeStorage.txt';

    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
        // constructor body
    }

    public function setStoragePath($path)
    {
        $this->path = $path;
    }

    public function cleanStorage()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    protected function read()
    {
        $collection = new Collection();
        $serializedCollection = '';

        $handle = fopen($this->path, "r");
        if (!$handle) {
            return $collection;
        }

        $contents = '';

        while (!feof($handle)) {
            $serializedCollection .= fread($handle, 8192);
        }
        fclose($handle);

        if ($serializedCollection) {
            $collection = unserialize($serializedCollection);
        }

        return $collection;
    }

    protected function write($collection)
    {
        $serializedCollection = serialize($collection);
        $handle = fopen($this->path, "w");
        if ($handle !== false) {
            fwrite($handle, $serializedCollection);
            fclose($handle);
            return true;
        }
        return false;
    }

    public function update($models)
    {
        $collection = $this->read();
        $mergedCollection = null;


        if ($collection) {
            $mergedCollection = $collection->merge($models);
        }
        return $this->write($mergedCollection);
    }

    public function delete($models)
    {
    }

    public function search(Builder $builder)
    {
        $query = $builder->query;
        $collection = $this->read();

        if (!$collection) {
            return null;
        }

        $res = new Collection();
        $collection->each(function ($item, $index) use ($query, $res) {
            $arr = $item->toArray();
            foreach ($arr as $k => $v) {
                if (strstr($v, $query)) {
                    $res->push($item);
                }
            }
        });
        return $res;
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
    }

    public function mapIds($results)
    {
    }

    public function map($results, $model)
    {
    }

    public function getTotalCount($results)
    {
    }
}
