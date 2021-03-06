<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: RedisCache.inc
| Author: RobiNN
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
namespace PHPFusion\Cache\Storage;

use PHPFusion\Cache\CacheException;
use PHPFusion\Cache\ICache;

/**
 * Class RedisCache
 *
 * @package PHPFusion\Cache\Storage
 */
class RedisCache implements ICache {
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * RedisCache constructor.
     *
     * @param $config
     *
     * @throws CacheException
     */
    public function __construct($config) {
        if (class_exists('\Redis')) {
            $this->redis = new \Redis();
        } else {
            throw new CacheException('Failed to load Redis Class.');
        }

        foreach ($config['redis_hosts'] as $host) {
            list($host, $port, $database, $password) = array_pad(explode(':', $host, 4), 4, NULL);

            $host = ($host !== NULL) ? $host : '127.0.0.1';
            $port = ($port !== NULL) ? $port : 6379;
            $database = ($database !== NULL) ? $database : 0;

            if ($this->redis->connect($host, $port) === FALSE) {
                throw new CacheException('Could not connect to Redis server. Please check host and port.');
            }

            if ($password != NULL && $this->redis->auth($password) === FALSE) {
                throw new CacheException('Could not authenticate with Redis server. Please check password.');
            }

            if ($database != 0 && $this->redis->select($database) === FALSE) {
                throw new CacheException('Could not select Redis database. Please check database setting.');
            }
        }
    }

    /**
     * Save data in cache
     *
     * @param string $key cache key
     * @param mixed  $data
     */
    public function set($key, $data) {
        $this->redis->set($key, $data);
    }

    /**
     * Return data by key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key) {
        return $this->redis->get($key);
    }

    /**
     * Delete data from cache
     *
     * @param string $key
     */
    public function delete($key) {
        $this->redis->del($key);
    }

    /**
     * Delete all data from cache
     */
    public function flush() {
        $this->redis->flushAll();
    }
}
