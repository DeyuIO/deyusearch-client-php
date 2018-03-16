<?php

namespace DeyuSearch\Frameworks\ThinkPHP;

use think\Config;

class Builder
{
    public function __construct($model, $query)
    {
        $this->model = $model;
        $this->query = $query;
    }

    public function select()
    {
        $results = $this->model->searchEngine()->search($this);
        return $this->model->searchEngine()->map($results, $this->model);
    }

    public function paginate($listRows = null, $simple = false, $config = [])
    {
        if (is_int($simple)) {
            $total  = $simple;
            $simple = false;
        }
        if (is_array($listRows)) {
            $config   = array_merge(Config::get('paginate'), $listRows);
            $listRows = $config['list_rows'];
        } else {
            $config   = array_merge(Config::get('paginate'), $config);
            $listRows = $listRows ?: $config['list_rows'];
        }

        /** @var Paginator $class */
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\paginator\\driver\\' . ucwords($config['type']);
        $page  = isset($config['page']) ? (int) $config['page'] : call_user_func([
            $class,
            'getCurrentPage',
        ], $config['var_page']);

        $page = $page < 1 ? 1 : $page;

        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);

        $engine = $this->model->searchEngine();

        $results = $engine->paginate($this, $listRows, $page);

        if (!isset($total) && !$simple) {

            // $bind    = $this->bind;
            $total   = $engine->getTotalCount($results);
            // $results = $this->options($options)->bind($bind)->page($page, $listRows)->select();
        } elseif ($simple) {
            // $results = $this->limit(($page - 1) * $listRows, $listRows + 1)->select();
            $total   = null;
        } else {
            // $results = $this->page($page, $listRows)->select();
        }

        // dump($results);
        // exit;

        return $class::make($results['hits'], $listRows, $page, $total, $simple, $config);
    }
}
