<?php 

namespace DeyuSearch\Frameworks\ThinkPHP;

trait DeyuSearchable
{
    public $chunk_limit = 500;

    public static function init()
    {
        static::registerEvents();
    }

    /**
     * 注册模型事件
     */
    public static function registerEvents()
    {
        static::event('after_write', function ($model) {
            $model->searchEngine()->update([$model]);
        });

        static::event('after_delete', function ($model) {
            dump($model);exit;
            $model->searchEngine()->delete([$model]);
        });

    }


    /**
     * 批量同步模型
     */
    public static function searchable(Array $models)
    {
        if (empty($models)) {
            return;
        }

        $models[0]->searchEngine()->update($models);
    }

    /**
     * 批量删除
     */
    public static function unsearchable(Array $models)
    {
        if (empty($models)) {
            return;
        }

        $models[0]->searchEngine()->delete($models);
    }

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

    public function makeAllUnsearchable($callback = null)
    {
        $page = 1;

        do {
            $models = $this->order($this->getPk())->limit($this->chunk_limit)->page($page)->select();

            if (empty($models)) {
                break;
            }

            $this->searchEngine()->delete($models);

            if (is_callable($callback)) {
                $callback(end($models)->getData($this->getPk()));
            }

            $page++;
        } while (count($models) == $this->chunk_limit);
    }
}