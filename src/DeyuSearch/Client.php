<?php

namespace DeyuSearch;

class Client
{

    const VERSION = '0.0.1';
    const GATEWAY = 'http://api.deyuapi.com';

    public function __construct($appid, $appkey)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
    }

    public function getUserAgent()
    {
        return 'DeyuSearch PHP Client ' . self::VERSION;
    }

    public function initIndex($index_name)
    {
        if (empty($index_name)) {
            throw new DeyuException('Invalid index name: empty string');
        }

        return new Index($this, $index_name);
    }

    public function request($path, $method, $data = null, $headers = [])
    {
        $curl_handler = curl_init();
        $default_headers = array(
            'X-Deyu-API-Id' => $this->appid,
            'X-Deyu-API-Key'        => $this->appkey,
            'Content-type'             => 'application/json',
        );

        $curl_headers = array();
        foreach ($default_headers as $key => $value) {
            $curl_headers[] = $key.': '.$value;
        }

        curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl_handler, CURLOPT_USERAGENT, $this->getUserAgent());
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handler, CURLOPT_FAILONERROR, true);
        curl_setopt($curl_handler, CURLOPT_ENCODING, '');
        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($curl_handler, CURLOPT_CAINFO, $this->caInfoPath);

        curl_setopt($curl_handler, CURLOPT_URL, self::GATEWAY . $path);
        curl_setopt($curl_handler, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl_handler, CURLOPT_FAILONERROR, false);

        switch ($method) {
            case 'GET':
                curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($curl_handler, CURLOPT_HTTPGET, true);
                curl_setopt($curl_handler, CURLOPT_POST, false);
                break;

            case 'POST':
                $body = $data ? json_encode($data) : '';
                // dump($body);
                curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl_handler, CURLOPT_POST, true);
                curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $body);
                break;

            case 'DELETE':
                curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl_handler, CURLOPT_POST, false);
                break;

            case 'PUT':
                $body = $data ? json_encode($data) : '';
                curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl_handler, CURLOPT_POST, true);
                break;
        }

        $response = curl_exec($curl_handler);

        $http_status = (int) curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
        $error = curl_error($curl_handler);

        if (!empty($error)) {
            throw new \Exception($error);
        }

        if ($http_status === 0 || $http_status === 503) {
            curl_close($curl_handler);
            return;
        }

        $answer = json_decode($response, true);

        if (intval($http_status / 100) == 4) {
            throw new DeyuException(isset($answer['message']) ? $answer['message'] : $http_status.' error', $http_status);
        } elseif (intval($http_status / 100) != 2) {
            throw new \Exception($http_status.': '.$response, $http_status);
        }

        return $answer;
    }

    /**
     * 获取索引列表
     */
    public function listIndexes()
    {
        return $this->request(
            "/v1/indices/" ,
            'GET'
        );
    }

    /**
     * 删除索引
     */
    public function deleteIndex($index_name)
    {
        return $this->request(
            "/v1/indices/" . urlencode($index_name),
            'DELETE'
        );
    }
}