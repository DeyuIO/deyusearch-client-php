<?php

namespace DeyuSearch\Frameworks\ThinkPHP;

use DeyuSearch\Client;
use think\Config;

class Engine
{
    public function __construct()
    {
        $this->client = new Client(config('deyu.appid'), config('deyu.appkey'));
    }

    public function search($builder)
    {
        return $this->performSearch($builder, array_filter([
            // 'numericFilters' => $this->filters($builder),
            // 'hits_per_page' => $builder->limit,
        ]));

    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        $deyu = $this->client->initIndex($builder->model->searchableAs());

        if (method_exists($builder->model, 'getSearchSettings')) {
            $options = array_merge($options, $builder->model->getSearchSettings());
        }

        $options['query'] = $builder->query;

        return $deyu->search($options);
    }


    public function update($models) 
    {
        if (empty($models)) {
            return;
        }

        $index = $this->client->initIndex($models[0]->searchableAs());

        // if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
        //     $models->each->pushSoftDeleteMetadata();
        // }

        $data = [];
        foreach ($models as $model) {
            array_push($data, array_merge(['object_id' => $model->getData($model->getPk())], $model->toArray()));
        }

        $index->addObjects($data);
    }

    public function map($results, $model)
    {
        if (empty($results['hits'])) {
            return [];
        }
        return $model->where($model->getPk(), 'in', array_column($results['hits'], 'object_id'))->select();
    }


    public function paginate(Builder $builder, $hits_per_page, $page)
    {
        return $this->performSearch($builder, [
            'hits_per_page' => $hits_per_page,
            'page' => $page - 1,
        ]);
    }

    public function getTotalCount($results)
    {
        return $results['total'];
    }

    public function delete($models) 
    {
        $index = $this->client->initIndex($models[0]->searchableAs());

        $object_ids = [];
        foreach ($models as $model) {
            array_push($object_ids, $model->getData($model->getPk()));
        }

        $index->deleteObjects($object_ids);
    }
}
