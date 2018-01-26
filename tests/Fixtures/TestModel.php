<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['id', 'message'];

    public function searchableAs()
    {
        return 'index_name';
    }

    public function getKey()
    {
        return $this->id;
    }

    public function toSearchableArray()
    {
        return [];
    }

    public function scoutMetadata()
    {
        return [];
    }
}
