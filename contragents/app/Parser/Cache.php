<?php

namespace App\Parser;

use Memcache;

class Cache
{
    private $instance;

    function __construct() {
        $this->instance = new Memcache;
        $this->instance->connect('localhost', 11211);
    }

    function get(string $key) {
        return $this->instance->get($key);
    }

    function set(string $key, $value) {
        $this->instance->set($key, $value, MEMCACHE_COMPRESSED, 3600);
    }
}
