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
        $data = [
            'collection' => new Collection(),
            'invertedIndex' => []
        ];
        $serializedData = '';

        $handle = fopen($this->path, "r");
        if (!$handle) {
            return $data;
        }

        $contents = '';

        while (!feof($handle)) {
            $serializedData .= fread($handle, 8192);
        }
        fclose($handle);

        if ($serializedData) {
            $data = unserialize($serializedData);
        }

        return $data;
    }

    protected function write($data)
    {
        $serializedData = serialize($data);
        $handle = fopen($this->path, "w");
        if ($handle !== false) {
            fwrite($handle, $serializedData);
            fclose($handle);
            return true;
        }
        return false;
    }

    public function update($models)
    {
        $data = $this->read();
        $invertedIndex = $data['invertedIndex'];
        $collection = $data['collection'];
        $collection = $collection->merge($models);

        $modelKeys = $models->modelKeys();


        // clean invertedIndex
        foreach ($invertedIndex as $token => $list) {
            foreach ($list as $k => $entity) {
                foreach ($modelKeys as $modelKey) {
                    if ($entity['id'] === $modelKey) {
                        unset($list[$k]);
                    }
                }
            }
        }

        // invertedIndexing
        $models->each(function ($item, $i) use (&$invertedIndex) {
            $arr = $item->toSearchableArray();
            foreach ($arr as $k => $v) {
                if ($k == 'id') {
                    continue;
                }
                $tokens = str_split($v);
                foreach ($tokens as $offset => $token) {
                    if (!array_key_exists($token, $invertedIndex)) {
                        $invertedIndex[$token] = [];
                    }
                    $invertedIndex[$token][] = [
                        'id' => $arr['id'],
                        'field' => $k,
                        'offset' => $offset
                    ];
                }
            }
        });
        // dd($invertedIndex);
        $data['invertedIndex'] = $invertedIndex;
        $data['collection'] = $collection;
        return $this->write($data);
    }

    public function delete($models)
    {
    }

    public function search(Builder $builder)
    {
        $term = $builder->query;
        $data = $this->read();
        $invertedIndex = $data['invertedIndex'];
        $collection = $data['collection'];

        if (!$invertedIndex) {
            return null;
        }

        $res = new Collection();
        $tokens = str_split($term);

        $matchedInvertedIndex = [];

        foreach ($tokens as $token) {
            // 如果有 token 沒有被建立過 invertedIndex，則代表沒有符合的文件
            if (!array_key_exists($token, $invertedIndex)) {
                // not found
                return $res;
            }

            // 把符合的 token index 額外取出
            $list = $invertedIndex[$token];
            $matchedInvertedIndex[$token] = $list;
        }

        // dd($invertedIndex);


        $matchedIds = [];
        foreach ($matchedInvertedIndex as $token => $list) {
            foreach ($list as $item) {
                $id = $item['id'];
                if (!array_key_exists($id, $matchedIds)) {
                    $matchedIds[$id] = 0;
                }
                $matchedIds[$id]++;
            }
        }

        // match 結果的數量要 >= token 數才算符合
        $matchedIds = array_filter($matchedIds, function ($matchedCount) use ($tokens) {
            return $matchedCount >= count($tokens);
        });

        arsort($matchedIds);

        // dd($matchedIds);

        $resultCollection = new Collection();
        foreach (array_keys($matchedIds) as $id) {
            $resultCollection->push($collection->where('id', $id)->first());
        }
        return $resultCollection;
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
