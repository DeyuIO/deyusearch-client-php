<?php 

namespace DeyuSearch\Frameworks\ThinkPHP;

trait DeyuSearchable
{
    public $chunk_limit = 5;
    public $highlights = [];

    public function getSearchSettings()
    {
        return property_exists($this, 'searchSettings') ? $this->searchSettings : [];
    }

    public function search($query)
    {
        return new Builder(new static, $query);
    }

    // 获取索引名，可以被覆盖
    public function searchableAs()
    {
        return $this->getTable();
    }

    public function searchEngine()
    {
        static $engine;

        if (is_null($engine)) {
            $engine = new Engine();
        }

        return $engine;
    }

    public function makeAllSearchable($callback = null)
    {
        $page = 1;
        // $records = $model->all();
        do {
            $models = $this->order($this->getPk())->limit($this->chunk_limit)->page($page)->select();

            if (empty($models)) {
                break;
            }

            $this->searchEngine()->update($models);

            if (is_callable($callback)) {
                $callback(end($models)->getData($this->getPk()));
            }

            $page++;
        } while (count($models) == $this->chunk_limit);
    }

}