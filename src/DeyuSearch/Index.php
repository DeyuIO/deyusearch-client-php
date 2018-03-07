<?php

namespace DeyuSearch;

class Index
{
    public function __construct(Client $client, $index_name)
    {
        $this->client = $client;
        $this->index_name = $index_name;
        $this->url_index_name = urlencode($index_name);
    }

    public function addObject($body)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}" ,
            'POST',
            $body
        );
    }

    public function saveObject($body)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/" . urlencode($body['object_id']),
            'PUT',
            $body
        );
    }

    public function getObject($object_id, $attributes = [])
    {
        $url = "/v1/indices/{$this->url_index_name}/" . urlencode($object_id);
        if (!empty($attributes)) {
            $url .= '?attributes=' . implode(',', $attributes);
        }
        return $this->client->request(
            $url,
            'GET'
        );
    }

    public function getObjects($object_ids, $attributes = [])
    {
        if (empty($object_ids)) {
            throw new Exception('no object_id provided');
        }

        $url = '/v1/indices/*/objects';
        if (!empty($attributes)) {
            $url .= '?attributes=' . implode(',', $attributes);
        }

        $requests = [];
        foreach ($object_ids as $object_id) {
            array_push($requests, ['index_name' => $this->url_index_name, 'object_id' => $object_id]);
        }

        return $this->client->request(
            $url,
            'POST',
            ['requests' => $requests]
        );
    }


    public function saveObjects($objects, $object_id_key = 'object_id')
    {
        $requests = $this->buildBatch('updateObject', $objects, true, $object_id_key);

        return $this->batch($requests);
    }

    public function partialUpdateObject($body)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/" . urlencode($body['object_id']) . '/partial',
            'POST',
            $body
        );
    }

    public function partialUpdateObjects($objects, $object_id_key = 'object_id')
    {
        $requests = $this->buildBatch('partialUpdateObject', $objects, true, $object_id_key);

        return $this->batch($requests);
    }

    public function deleteObject($object_id)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/{$object_id}",
            'DELETE'
        );
    }

    public function addObjects($objects, $object_id_key = 'object_id')
    {
        $requests = $this->buildBatch('addObject', $objects, true, $object_id_key);

        return $this->batch($requests);
    }

    public function batch($requests)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/batch",
            'POST',
            $requests
        );
    }

    private function buildBatch($action, $objects, $with_object_id = true, $object_id_key = 'object_id')
    {
        $requests = array();
        foreach ($objects as $object) {
            $request = array('action' => $action, 'body' => $object);
            if ($with_object_id && array_key_exists('object_id', $object)) {
                $request['object_id'] = (string) $object[$object_id_key];
            }
            array_push($requests, $request);
        }

        return array('requests' => $requests);
    }

    public function deleteObjects($objects)
    {
        $object_ids = [];
        foreach ($objects as $key => $id) {
            $object_ids[$key] = ['object_id' => $id];
        }
        $requests = $this->buildBatch('deleteObject', $object_ids, true);

        return $this->batch($requests);
    }

    public function search($params)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/query",
            'POST',
            $params
        );
    }

    public function setSettings($params)
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/settings",
            'PUT',
            $params
        );
    }

    public function getSettings()
    {
        return $this->client->request(
            "/v1/indices/{$this->url_index_name}/settings",
            'GET',
            $params
        );
    }
}