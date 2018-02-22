<?php

namespace DeyuSearch;

class ClientContext
{
    const VERSION = '0.0.1';

    public function __construct($appid, $appkey)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
    }

    public function getUserAgent()
    {
        return 'DeyuSearch PHP Client ' . self::VERSION;
    }
}