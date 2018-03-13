<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Driver\Session;

class RedisSession implements \SessionHandlerInterface
{
	private $redis;
	private $host = '127.0.0.1';
	private $port = 6379;
	private $auth = '';
	private $timeout = 0;

	public function __construct($host, $port, $auth, $timeout)
	{
		$this->host = $host;
		$this->port = $port;
		$this->auth = $auth;
		$this->timeout = $timeout;
	}

	public function open($savePath, $sessionName)
	{
		if( !extension_loaded("redis") )
		{
			throw new Exception('please install redis extension',500);
		}
		$this->redis = new \Redis();
		if( !$this->redis->connect($this->host, $this->port) )
		{
			throw new Exception('can not connect session redis',500);
			return false;
		}
		else
		{
			if( !$this->redis->auth($this->auth) )
			{
				throw new Exception('session redis password is not correct',500);
				return false;
			}
		}
		return true;
	}
	public function close()
	{
		$this->redis->close();
		return true;
	}
	public function read($id)
	{
		$val = $this->redis->get($id);
		if( !$val )
		{
			return '';
		}
		return $val;
	}
	public function write($id, $data)
	{
		return $this->redis->set($id, $data, $this->timeout);

	}
	public function destroy($id)
	{
		if( !$this->redis->del($id)>0 )
		{
			return false;
		}
		return true;
	}
	public function gc($maxlifetime)
	{
		return true;
	}

}

