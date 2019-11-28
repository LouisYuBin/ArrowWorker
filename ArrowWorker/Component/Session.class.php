<?php

namespace ArrowWorker\Component;


class Session
{
    /**
     * @var \Memcached|\Redis
     */
    protected $handler;
    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 6379;

    /**
     * @var string
     */
    protected $userName = '';

    /**
     * @var string
     */
    protected $auth = '';

    /**
     * @var int
     */
    protected $timeout = 0;


    /**
     * MemcachedSession constructor.
     * @param string $host
     * @param int $port
     * @param string $userName
     * @param string $password
     * @param int $timeout
     */
    public function __construct(string $host, int $port, string $userName, string $password, int $timeout)
    {
        $this->host = $host;
        $this->port = $port;
        $this->auth = $password;
        $this->timeout  = $timeout;
        $this->userName = $userName;
    }
}