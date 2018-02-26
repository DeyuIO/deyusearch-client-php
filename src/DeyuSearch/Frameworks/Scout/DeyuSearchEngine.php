<?php

namespace DeyuSearch\Frameworks\Scout;

use DeyuSearch\Client as Deyu;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class DeyuSearchEngine extends Engine
{
    public function __construct(Deyu $deyu)
    {
        $this->deyu = $deyu;
    }
    
    public function update($models) 
    {
        // var_export($this->deyu);
        if ($models->isEmpty()) {
            return;
        }

        $index = $this->deyu->initIndex($models->first()->searchableAs());

        if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
            $models->each->pushSoftDeleteMetadata();
        }

        $data = $models->map(function ($model) {
            $array = array_merge(
                $model->toSearchableArray(), $model->scoutMetadata()
            );

            if (empty($array)) {
                return;
            }

            return array_merge(['object_id' => $model->getKey()], $array);
        })->filter()->values()->all();

        $index->addObjects($data);

    }

    public function delete($models) 
    {
        $index = $this->deyu->initIndex($models->first()->searchableAs());

        $index->deleteObjects(
            $models->map(function ($model) {
                return $model->getKey();
            })->values()->all()
        );
    }

    public function map($results, $model)
    {
        if (count($results['hits']) === 0) {
            return Collection::make();
        }

        $builder = in_array(SoftDeletes::class, class_uses_recursive($model))
                    ? $model->withTrashed() : $model->newQuery();

        $models = $builder->whereIn(
            $model->getQualifiedKeyName(),
            collect($results['hits'])->pluck('object_id')->values()->all()
        )->get()->keyBy($model->getKeyName());

        return Collection::make($results['hits'])->map(function ($hit) use ($model, $models) {
            $key = $hit['object_id'];

            if (isset($models[$key])) {
                if (isset($hit['_highlight'])) {
                    $models[$key]->_highlight = $hit['_highlight'];
                }
                return $models[$key];
            }
        })->filter()->values();
    }

    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            // 'numericFilters' => $this->filters($builder),
            'hits_per_page' => $builder->limit,
        ]));
    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        $deyu = $this->deyu->initIndex(
            $builder->index ?: $builder->model->searchableAs()
        );

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $deyu,
                $builder->query,
                $options
            );
        }

        if (method_exists($builder->model, 'getSearchSettings')) {
            $options = array_merge($options, $builder->model->getSearchSettings());
        }

        $options['query'] = $builder->query;

        return $deyu->search($options);
    }

    public function paginate(Builder $builder, $hits_per_page, $page)
    {
        return $this->performSearch($builder, [
            'hits_per_page' => $hits_per_page,
            'page' => $page - 1,
        ]);
    }

    public function mapIds($results) 
    {
        return collect($results['hits'])->pluck('object_id')->values();
    }


    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['total'];
    }


    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

}