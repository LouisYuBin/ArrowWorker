<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\HttpServer;

use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use ArrowWorker\Std\Http\ResponseInterface;
use Swoole\Http\Response as SwResponse;

/**
 * Class Response
 * @package ArrowWorker
 */
class Response implements ResponseInterface
{

    /**
     * @var bool
     */
    private bool $isAllowCORS = false;

    private SwResponse $response;

    /**
     * Response constructor.
     * @param SwResponse $swResponse
     * @param bool $isAllowCORS
     */
    public function __construct(SwResponse $swResponse, bool $isAllowCORS=true)
    {
        $this->isAllowCORS = $isAllowCORS;
        $this->response = $swResponse;
    }

    /**
     * @return bool
     */
    public function getCORS(): bool
    {
        return $this->isAllowCORS;
    }

    /**
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public function writeJson(int $code, array $data = [], string $msg = ''):void
    {
        $this->setHeader("content-type", "application/json;charset=utf-8");
        $this->write(json_encode([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $msg
     */
    public function write(string $msg):void
    {
        if ($this->isAllowCORS) {
            $this->setCorsHeader();
        }
        $this->setHeader('Server', 'Arrow, Louis!');
        $this->response->end($msg);
        Log::debug("Response : {$msg}", [], __METHOD__);
    }

    /**
     * @param string $key
     * @param string $val
     * @return void
     */
    public function setHeader(string $key, string $val):void
    {
        $this->response->header($key, $val);
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status):void
    {
        $this->response->status($status);
    }

    /**
     * @param array $data
     * @return void
     */
    public function setHeaders(array $data):void
    {
        $response = Context::get(__CLASS__);
        foreach ($data as $key => $val) {
            $response->header($key, $val);
        }
    }


    /**
     * @param string $name
     * @param string $val
     * @param int $expire
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return void
     */
    public function setCookie(string $name, string $val, int $expire = 0, string $path = '/', string $domain = null, bool $secure = false, bool $httpOnly = true):void
    {
        $expire = ($expire === 0) ? 0 : time() + $expire;
        $this->response->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     *
     */
    public function setCorsHeader():void
    {
        $this->setHeaders([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Headers' => 'Origin,X-Requested-With,x_requested_with,Content-Type,Accept',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
        ]);
    }

}